<?php

namespace App\Util\Chart;

use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

// 处理折线图数据
class LineChartDataHandler
{
    public function reduce(Collection $collection, $seriesMap)
    {
        $seriesKeys = array_keys($seriesMap);
        $carry = [];
        foreach ($seriesKeys as $seriesKey) {
            $carry[$seriesKey] = [];
        }

        // $model必须有date字段
        return $collection->reduce(function ($carry, $model) use($seriesMap) {
            foreach ($seriesMap as $seriesKey => $column) {
                $carry[$seriesKey][$model->date] = $model->$column;
            }
            return $carry;
        }, $carry);

        // 对下面这段代码的翻译
//        $carry = [
//            'in_coin' => [],
//            'in_share_coin' => [],
//            'out_ticket' => [],
//            'out_share_ticket' => []
//        ];
//        $data = $collection->reduce(function($carry, $model) {
//            $carry['in_coin'][$model->date] = $model->put_coin;
//            $carry['in_share_coin'][$model->date] = $model->put_share_coin;
//
//            $carry['out_ticket'][$model->date] = $model->refund_ticket;
//            $carry['out_share_ticket'][$model->date] = $model->refund_share_ticket;
//            return $carry;
//        }, $carry);
    }

    // 填充series(折线图)数据中缺失的日期
    // $columns: 每个series的名字
    public function fill($data, $startDate, $endDate, $columns): array
    {
        $period = new CarbonPeriod($startDate, '1 day', $endDate);

        $dates = [];

        foreach ($period as $carbon) {
            $day = $carbon->format('Y-m-d');
            $dates[] = $day;

            foreach ($columns as $column) {
                if (!isset($data[$column][$day])) {
                    $data[$column][$day] = 0;
                }
            }
        }

        $series = [];
        foreach ($columns as $column) {
            ksort($data[$column]);
            $series[$column] = array_values($data[$column]);
        }

        return [$dates, $series];
    }
}
