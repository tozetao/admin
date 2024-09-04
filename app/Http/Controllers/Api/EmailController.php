<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Repository\PlayerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $recipientId = $request->get('recipient_id');
        $page = $request->get('page');

        $from = parse_from_date($request->get('from'));
        $to   = parse_to_date($request->get('to'));

        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $query = MailLog::query();

        if ($recipientId) {
            $query->where('to_id', $recipientId);
        }

        if ($from && $to) {
            $query->where('time', '>=', $from)
                ->where('time', '<=', $to);
        }



        //
        $isExport = $request->get('is_export');
        if ($isExport) {
            $models = $query->with('sender', 'recipient')->orderBy('time', 'desc')->get();
            $this->export($models);
        } else {
            $total = $query->count();
            $models = $query->with('sender', 'recipient')
                ->orderBy('time', 'desc')
                ->offset($offset)
                ->limit($pageSize)
                ->get();
            return $this->data([
                'list' => $this->translate($models),
                'total' => $total
            ]);
        }
    }

    private function export(Collection $models)
    {
        $data = $this->translate($models);

        foreach ($data as $key => $row) {
            $attachment = '';
            $items = $row['items'];
            $n = count($items) - 1;
            if ($n + 1) {
                $i = 0;
                while ($i < $n) {
                    $attachment .= $items[$i]['name'] . '*' . $items[$i]['num'] . '; ';
                    $i++;
                }
                $attachment .= $items[$i]['name'] . '*' . $items[$i]['num'];
            }

            $data[$key]['attachment'] = $attachment;
            $data[$key]['status'] = $this->translateStatus($row['status']);
            $data[$key]['type'] = $this->translateType($row['type']);
        }

        $columns = [
            'to_id' => '玩家ID',
            'recipient' => '玩家昵称',
            'attachment' => '附件',
            'type' => '邮件类型',
            'status' => '状态',
            'title' => '标题',
            'content' => '内容',
            'sender' => '发送人',
            'created_at' => '创建事件'
        ];

        $excelHandler = app('excel_handler');
        $excelHandler->write($columns, $data, '系统兑换统计');
    }

    public function translateType($value) {
        switch (intval($value)) {
            case self::Single:
                return '单人邮件';
            case self::All:
                return '全服邮件';
            case 7:
                return '周排行邮件';
            default:
                return '-';
        }
    }

    private function translateStatus($status): string
    {
        switch(intval($status)) {
            case 0:
                return '未领取';
            case 1:
                return '已领取';
            case 2:
                return '已删除';
            default:
                return '-';
        }
    }

    private function translate(Collection $models): array
    {
        return $models->map(function (MailLog $model) {
            $sender = $model->sender ? $model->sender->account : '-';
            $recipient = $model->recipient ? $model->recipient->openid : '-';
            $recipientIcon = $model->recipient ? $model->recipient->icon : '';

            return [
                'id' => $model->id,
                'created_at' => to_datetime_string($model->time),
                'received_at' => to_datetime_string($model->reward_time),
                'deleted_at' => to_datetime_string($model->delete_time),
                'status' => $model->status,
                'type' => $model->type,
                'from_id' => $model->from_id,
                'to_id' => $model->to_id,
                'title' => $model->title,
                'content' => $model->content,
                'recipient' => $recipient,
                'recipient_icon' => $recipientIcon,
                'sender' => $sender,
                'items' => $model->getItems()
            ];
        })->all();
    }

    const All = 2;
    const Single = 1;

    // 发送邮件
    public function create(Request $request, PlayerRepository $playerRepository)
    {
        $this->validate($request, [
            'type' => 'required|in:1,2',
            'title' => 'required|string|max:50',
            'content' => 'nullable|string|max:500',
            'coin' => 'nullable|integer',
            'ticket' => 'nullable|integer',
            'diamond' => 'nullable|integer',
//            // $items = [[id, num]...], id是商品配置表ID，num是发送数量
//            'items' => 'nullable|array'
        ]);

        $user = Auth::user();

        $type = $request->post('type');
        $playerId = (int)$request->post('player_id', 0);

        if (self::All == $type) {
            // 全服发送，玩家id=0
            $playerId = 0;
        } else {
            // 个人发送，一定需要玩家ID
            if (!$playerRepository->exists($playerId)) {
                return $this->fail(trans('err.not_found'));
            }
        }

        // 解析注册时间
        $registerInterval = [
            'start' => parse_from_date($request->post('start')),
            'end' => parse_to_date($request->post('end'))
        ];

        $title = $request->post('title');
        $content = $request->post('content') ?: '';

        $coin = $request->post('coin') ?: 0;
        $coin = intval($coin);
        if ($coin < 0) {
            return $this->fail('金币不能小于0');
        }

        $diamond = $request->post('diamond') ?: 0;
        $diamond = intval($diamond);
        if ($diamond < 0) {
            return $this->fail('钻石不能小于0');
        }

        $shareCoin = $request->post('share_coin') ?: 0;
        $shareCoin = intval($shareCoin);
        if ($shareCoin < 0) {
            return $this->fail('5G乐享币不能小于0');
        }

        $gameApi = app('game_api');
        $result = $gameApi->sendEmail($title, $content, $playerId, $coin, $diamond, $shareCoin, $registerInterval, $user->getAuthIdentifier());
        if (!$result) {
            return $this->fail('failed');
        }

        return $this->success();
    }

    private function itemsConvert(array $items): array
    {
        $result = [];
        if (!$items) {
            return $result;
        }
        foreach ($items as $item) {
            $configId = (int)$item[0];
            $num      = (int)$item[1];
            $result[] = [$configId, $num];
        }
        return $result;
    }

    public function delete(Request $request)
    {
        $mailId = $request->post('mail_id');
        $model = MailLog::findOrFail($mailId);
        $gameApi = app('game_api');

        if (!$gameApi->removeMail(intval($model->to_id), intval($model->id))) {
            return $this->fail(trans('err.failure'));
        }
        return $this->success();
    }
}
