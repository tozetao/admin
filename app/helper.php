<?php

use \Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Carbon;

function encrypt_password($password): string
{
    return Hash::make($password);
}

function validate_password($plain, $password): bool
{
    return Hash::check($plain, $password);
}


function to_datetime_string($timestamp) {
    if (empty($timestamp)) {
        return '-';
    }
    return date('Y-m-d H:i:s', $timestamp);
}

function check_date($str) {
    return preg_match_all('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $str) ||
    preg_match_all('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/', $str);
}

// 将一个日期格式化为其零点时间戳。
function parse_from_date($start, $isStartDay = false)
{
    if (!check_date($start)) {
        return 0;
    }
    if ($isStartDay) {
        return Carbon::parse($start)->startOfDay()->timestamp;
    }
    return Carbon::parse($start)->timestamp;
}

// 解析结束时间。函数首先会将一个$end格式化为所在日期的零点时间戳，再为其增加86399秒，也就是所在日期第二天零点时间戳的前一秒。
function parse_to_date($end, $isEndDay = false)
{
    if (!check_date($end)) {
        return 0;
    }

    $carbon = Carbon::parse($end);
    if ($isEndDay) {
        return $carbon->startOfDay()->timestamp + 86399;
    }
    return $carbon->timestamp;
}

// 获取一个月份的最后一天
function get_month_end_day($month): string
{
    $carbon = Carbon::parse($month, config('app.timezone'));
    if ($carbon->isCurrentMonth()) {
        return Carbon::today(config('app.timezone'))
            ->endOfDay()
            ->toDateTimeString();
    }
    return Carbon::parse($month, config('app.timezone'))
        ->endOfMonth()
        ->toDateTimeString();
}

// 命名空间 + 控制器 + action
function resolve_action(\Illuminate\Http\Request $request): string
{
    $action = $request->route()->getAction();
    return substr($action['controller'], strrpos($action['controller'], '\\') + 1);
}

function resolve_multi_status($statusList): array
{
    // [[id,status]...]，最多更新10条
    $list = \json_decode($statusList);
    if (count($list) > 10) {
        throw new ErrorException('The number of choices is too many.');
    }
    $res = [];
    foreach ($list as $item) {
        $id = $item[0] ?? 0;
        if ($id) {
            $value = $item[1] ?? 0;
            $res[$id] = $value;
        }
    }
    return $res;
}

/**
 * $array = [
 *     id => [id, pid],
 *     id => [id, pid],
 *     ...
 * ]
 * $array参数必须是这种结构的数组
 */
function build_tree($array): array
{
    $list = [];
    foreach ($array as $item) {
        if (isset($array[$item['pid']]) && !empty($array[$item['pid']])) {
            $array[$item['pid']]['children'][] = &$array[$item['id']];
        } else {
            $list[] = &$array[$item['id']];
        }
    }
    return $list;
}

// 返回图片url地址的真实路径
function real_image_path($url) {
    $path = public_path(str_replace(env('ASSET_URL'), '', $url));
    return realpath($path);
}

// 返回图片的url地址，$path必须是相对于本项目public目录的地址，且必须带有/
function image_url($path): string
{
    return config('app.asset_url') . $path;
}

// 是否整数或整数字符串
function is_int_number($value): bool
{
    if (is_int($value)) {
        return true;
    }
    return is_string($value) && (preg_match('/^\d+$/', $value) === 1);
}

// $str like "name=z&age=15&height=25"
function parse_command_params($str): array
{
    $array = explode('&', $str);
    $params = [];
    foreach ($array as $item) {
        $kv = explode('=', $item);
        if (count($kv) == 2) {
            $params[$kv[0]] = $kv[1];
        }
    }
    return $params;
}

function lock_key($key): string
{
    $prefix = env('REDIS_PREFIX');
    if (empty($prefix)) {
        throw new \ErrorException('The prefix of lock key must be configured.');
    }
    return $prefix . $key;
}
