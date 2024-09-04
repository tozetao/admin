<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\Machine;
use App\Models\User;
use App\Repository\AdminRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActionLogController extends Controller
{

    public function index(Request $request, AdminRepository $adminRepository)
    {
        $serverNo = $this->getActiveServerNo(Auth::user(), $request);

        // 管理员账号
        $adminId = 0;
        if ($account = $request->get('account')) {
            $admin = $adminRepository->findOneBy([
                'account' => $account
            ]);
            if (empty($admin)) {
                return $this->data(['list' => [], 'total' => 0]);
            }
            $adminId = $admin->id;
        }

        $start = parse_from_date($request->get('start'), true);
        $end = parse_to_date($request->get('end'), true);

        $query = ActionLog::query()
            ->where('server_no', $serverNo);

        // 日期、操作类型
        if ($start && $end) {
            $query->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end);
        }

        if ($adminId) {
            $query->where('user_id', $adminId);
        }

        // 如果管理员不是1
        $user = Auth::user();
        if ($user->isChildAdmin()) {
            // 'yunwei', 'YunWei123', 'xiangge100'
            $query->whereNotIn('user_id', [1, 2, 210]);
        }

        $page = $request->get('page', 1);
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $count = $query->count();
        $collection = $query->orderBy('id', 'desc')
            ->limit($pageSize)
            ->offset($offset)
            ->get();

        $ids = $collection->pluck('user_id')->all();
//        $admins = $adminRepository->findIn();
        $admins = $this->findUsers($ids);

        $list = $collection->map(function ($model) use($admins) {
            $admin = $admins->where('id', $model->user_id)->first();
            $account = '-';
            if ($admin) {
                $account = $admin->account;
            }
            return [
                'account' => $account,
                'title' => $model->title,
                'content' => $model->content,
                'time' => to_datetime_string($model->created_at),
                'ip' => $model->ip,
            ];
        })->all();

        return $this->data([
            'total' => $count,
            'list' => $list
        ]);
    }

    private function findUsers($ids)
    {
        return User::query()
            ->whereIn('id', $ids)
            ->whereIn('type', [User::Admin, User::Distributor])
            ->get();
    }

    // title, content, action
}
