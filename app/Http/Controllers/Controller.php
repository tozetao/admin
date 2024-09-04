<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Repository\ServerConfigRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // TODO: 统一让前端传递server_no，通过middleware来验证server_no的处理方式更好。

//    // 该方法希望希望能够返回用户选择的子服ID，对于超管和经销商会返回他们选择的子服ID，对于子服管理员，会返回他们绑定的子服ID。
//    public function getScIdForChild(User $user, Request $request)
//    {
//        if ($user->belongsToCentralServer()) {
//            if ($scId = $request->header('X-Default-Sc-Id')) {
//                return $scId;
//            }
//            if ($user->isDistributor()) {
//                return $user->default_sc_id;
//            }
//        }
//        return $user->server_no;
//    }

    /**
     * 用于获取当前管理员正在操作的服务器ID。
     *
     * 对于超管、经销商该方法会返回他们选择的子服ID。对于子服管理员，会返回他们绑定的子服ID。
     * X-Default-Sc-Id请求头对应子服的ID，可以在后台选择。
     * 注：只用于子服的路由请求
     *
     * @param User $user
     * @param Request $request
     * @return array|mixed|string
     */
    public function getActiveServerNo(User $user, Request $request)
    {
        if ($user->belongsToCentralServer()) {
            if ($scId = $request->header('X-Default-Sc-Id')) {
                return $scId;
            }
            if ($user->isDistributor()) {
                return $user->default_sc_id;
            }
        }
        return $user->server_no;
    }

    // 对于中央服路由的请求，超管可以选择要操作的指定服务器，这时会根据请求的sc_id来决定要操作的服务器，也可以传递null查询全部服务器的数据；
    // 而经销商是根据请求头中的sc_id来决定要操作的服务器ID；默认子服用户是返回的他们绑定的sc_id。
    // 注：只用于中央服的路由请求
    public function getScIdForCentral(User $user, Request $request)
    {
        if ($user->isCentralAdmin()) {
            return $request->input('sc_id') ?: null;
        }
        if ($user->isDistributor()) {
            return $this->getDistributorServerConfigId($request);
        }
        return $user->server_no;
    }

    public function getDistributorServerConfigId(Request $request)
    {
        $serverNo = $request->header('X-Default-Sc-Id');
        $serverConfigRepository = app(ServerConfigRepository::class);
        $serverConfig = $serverConfigRepository->find($serverNo);
        if (!check_distributor_config($serverConfig)) {
            throw new ApiException('你没有权操作该服务配置!');
        }
        return $serverConfig->server_no;
    }

//    // 处理中央服路由。
//    // 有一些路由请求，超管可以在请求中传递要操作的服务器，比如在机台列表中选择要查询哪个服的机台，这时候机台ID是通过方法url参数或body中的。
//    // 而经销商是根据请求头中的sc_id来决定要操作的服务器ID；默认子服用户是返回的他们绑定的sc_id。
//    // 该方法用于处理在上面的情况下该如何获取server id
//    public function getChosenServerNo(User $user, Request $request)
//    {
//        if ($user->isCentralAdmin()) {
//            return $request->input('sc_id') ?: null;
//        }
//        if ($user->isDistributor()) {
//            return $this->getDistributorServerConfigId($request);
//        }
//        return $user->server_no;
//    }

    public function hasServerConfig(User $user, $serverNo): bool
    {
        if (empty($serverNo)) {
            return false;
        }

        if ($user->isChildAdmin()) {
            return $user->server_no == $serverNo;
        }

        $serverConfigRepository = app(ServerConfigRepository::class);
        $serverConfig = $serverConfigRepository->find($serverNo);

        if ($user->isDistributor()) {
            return check_distributor_config($serverConfig);
        }

        return $user->isCentralAdmin();
    }


    protected function parseMonth($month): array
    {
        $carbon = Carbon::parse($month, config('app.timezone'));

        $startDate = $carbon->toDateTimeString();
        $start     = $carbon->timestamp;
        $endDate   = get_month_end_day($month);
        $end       = Carbon::parse($endDate, config('app.timezone'))->timestamp;

        return [$startDate, $endDate, $start, $end];
    }

    protected function validateDate($startDate, $endDate, $days = 31)
    {
        if (empty($startDate) || empty($endDate)) {
            throw new ApiException('请选择查询时段');
        }

        $start = parse_from_date($startDate);
        $end = parse_to_date($endDate);
        $diffDays = abs($end - $start) / 86400;
        if ($diffDays > $days) {
            throw new ApiException(sprintf('查询时段最多支持%d天。', $days));
        }
    }

    public function success($message = null): \Illuminate\Http\JsonResponse
    {
        $defaultMessage = 'success';
        if ($message) {
            $defaultMessage = $message;
        }
        return response()->json([
            'code' => 0,
            'message' => $defaultMessage
        ])->setStatusCode(200);
    }

    public function fail($message, $status = 200, $errCode = 1): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $errCode,
            'message' => $message
        ])->setStatusCode($status);
    }

    public function data($data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => 0,
            'data' => $data
        ]);
    }
}






