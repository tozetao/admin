<?php

namespace App\Http\Controllers\Api;

use App\Biz\ServerConfigCreator;
use App\Biz\ServerConfigManager;
use App\Http\Controllers\Controller;
use App\Models\ServerConfig;
use App\Repository\ServerConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ServerConfigController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $page    = $request->get('page');
        $pageSize = 10;

        $offset = ($page - 1) * $pageSize;

        $user = Auth::user();

        $query = ServerConfig::query();

        if ($keyword) {
            $query->where('server_no', $keyword);
        }

        if ($user->isDistributor()) {
            $query->whereIn('agent_type', config('app.distributor_agent_types'));
        }

        $total = $query->count();
        $collection = $query->offset($offset)->limit($pageSize)->get();

        $data = $collection->map(function ($model) use($request) {
            $url = sprintf('%s/index.html#/login?code=%s', $request->getSchemeAndHttpHost(), $model->code);
            $phoneUrl = sprintf('%s/phone/#/login?code=%s', $request->getSchemeAndHttpHost(), $model->code);
            // index.html#/login?
            return [
                'server_no' => $model->server_no,
                'server_name' => $model->server_name,
                'stream_url' => $model->stream_url,
                'platform_type' => $model->platform_type,
                'agent_type' => $model->agent_type,
                'expired_at' => $model->expired_at,
                'expired_at_str' => to_datetime_string($model->expired_at),
                'created_at' => $model->created_at,
                'created_at_str' => to_datetime_string($model->created_at),
                'url' => $url,
                'phone_url' => $phoneUrl
            ];
        })->all();

        return $this->data([
            'list' => $data,
            'total' => $total
        ]);

        // mch_id: 商户id，merge_room: 是否合并房间。stream
    }

    public function create(Request $request, ServerConfigCreator $creator)
    {
        $user = Auth::user();

        if (!$user->isCentralAdmin()) {
            return $this->fail(trans('err.authorize'));
        }

        $this->validate($request, [
            'server_name' => 'required|min:2|max:30',   // 服务器名字
            'stream_url' => 'required|string|max:200',  // 视频推流地址
            'merge_room' => 'required|in:1,0',
            'mch_id' => 'required|string|max:100',  // 商户号id
            'agent_type' => 'required|is_int_number',
            'platform_type' => 'required'
        ], [], [
            'server_name' => '服务器名次',
            'stream_url' => '推流地址',
            'mch_id' => '商户号ID',
            'agent_type' => '代理',
            'platform_type' => '第三方平台'
        ]);

        // 房间合并: merge_room，1是合并、0不合并。
        // agent_type：代理类型，1是晞娱科技、2是蓝迪科技
        // system_id：平台类型，
        $serverName = $request->post('server_name');
        $streamUrl = $request->post('stream_url');
        $agentType = $request->post('agent_type');
        $platformType = $request->post('platform_type');
        $mergeRoom = $request->post('merge_room');
        $mchId = $request->post('mch_id');

        if ($creator->execute($user, $serverName, $streamUrl, $mchId, $mergeRoom, $platformType, $agentType)) {
            return $this->success();
        }

        return $this->fail('fail');
    }

    public function options(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $withDefaultValue = $request->query('with_default_value', 1);

        if ($user->isCentralAdmin()) {
            $collection = ServerConfig::all();
            $data = $this->translateOptions($collection, $withDefaultValue);
            return $this->data($data);
        }

        if ($user->isDistributor()) {
            $collection = ServerConfig::query()
                ->whereIn('agent_type', config('app.distributor_agent_types'))
                ->get();
            $data = $this->translateOptions($collection, $withDefaultValue);
            return $this->data($data);
        }

        return $this->data([]);
    }

    private function translateOptions(Collection $collection, $withDefaultValue)
    {
        // 1 => 5G富连网中心
        $func = function ($model) {
            return [
                'label' => $model->server_name . "  ({$model->server_no})",
                'value' => $model->server_no
            ];
        };
        $data = $collection->map($func)->all();
        if ($withDefaultValue) {
            array_unshift($data, [
                'label' => '请选择服务器',
                'value' => null
            ]);
        }
        return $data;
    }

    public function update(Request $request, ServerConfigRepository $repository, ServerConfigManager $manager)
    {
        $user = Auth::user();

        if (!$user->isCentralAdmin()) {
            return $this->fail(trans('err.authorize'));
        }

        // 过期时间、平台类型、mch_id
        $this->validate($request, [
            'mch_id' => 'required|string|max:100',  // 商户号id
            'platform_type' => 'required|integer',
            'expired_at' => 'nullable|date',
            'server_name' => 'required|string|min:1|max:20',
            'agent_type' => 'required|is_int_number',
        ]);

        $serverNo = $request->post('server_no');
        $serverConfig = $repository->findOneBy(['server_no' => $serverNo]);
        if (empty($serverConfig)) {
            return $this->fail(trans('err.not_found'));
        }

        $serverConfig->mch_id = $request->post('mch_id');
        $serverConfig->platform_type =  $request->post('platform_type');
        $serverConfig->code = $manager->generateCode($serverConfig->platform_type, $serverConfig->mch_id);
        $serverConfig->agent_type = $request->post('agent_type');

        $expiredAt = $request->post('expired_at');
        if (empty($expiredAt)) {
            $serverConfig->expired_at = 0;
        } else {
            $expiredAt = strtotime($expiredAt);
            if ($expiredAt !== false) {
                $serverConfig->expired_at = $expiredAt;
            }
        }

        // 更新名字
        $name = $request->post('server_name');
        if (is_string($name) && $name) {
            $serverConfig->server_name = $name;
        }

        if (!$serverConfig->save()) {
            return $this->fail(trans('err.update_failed'));
        }

        // 更新配置文件
        $gameConfigFile = new GameConfigFile($serverConfig->code, $serverConfig, env('SERVER_IP'));
        if (is_string($name) && $name) {
            $gameConfigFile->setTitle($name);
        }
//        if (is_string($name) && $name) {
//            $gameConfigFile = new GameConfigFile($serverConfig->code, $serverConfig, env('SERVER_IP'));
//            $gameConfigFile->setTitle($name);
//        }

        $repository->clearCache($serverNo);

        return $this->success();
    }

    public function show(Request $request, ServerConfigRepository $repository)
    {
        $user = Auth::user();

        if (!$user->isCentralAdmin()) {
            return $this->fail(trans('err.authorize'));
        }

        $serverConfig = $repository->findOneBy(['server_no' => $request->post('id')]);
        if (empty($serverConfig)) {
            return $this->fail(trans('err.not_found'));
        }
        return $this->data($serverConfig->toArray());
    }
}
