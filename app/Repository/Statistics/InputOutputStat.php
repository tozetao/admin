<?php
//
//namespace App\Repository\Statistics;
//
//use App\Models\GameLog;
//use App\Models\PlayerCurrencyStatLog;
//use Illuminate\Support\Facades\DB;
//
//class InputOutputStat
//{
//
//    private function buildCoinQuery(int $start, int $end, $playerIds): \Illuminate\Database\Eloquent\Builder
//    {
//        // 金币统计
//        $coinQuery = PlayerCurrencyStatLog::query()
//            ->select(DB::raw('sum(cost_value) as input, player_id'))
//            ->where('log_type', PlayerCurrencyStatLog::Coin);
//
//        if ($start && $end) {
//            $coinQuery->where('start', '>=', $start)
//                ->where('start', '<', $end);
//        }
//
//        $coinQuery->whereIn('type', [GameLog::TakeSeat, GameLog::PutCoin]);
//
//        if ($playerIds) {
//            if (is_array($playerIds)) {
//                $coinQuery->whereIn('player_id', $playerIds);
//            } else {
//                $coinQuery->where('player_id', $playerIds);
//            }
//        }
//
//        return $coinQuery;
//    }
//
//    public function getCoinStats(int $start, int $end, $playerIds, $offset, $limit)
//    {
//        $query = $this->buildCoinQuery($start, $end, $playerIds);
//        return $query->groupBy('player_id')
//            ->orderBy('input', 'desc')
//            ->offset($offset)
//            ->limit($limit)
//            ->get();
//    }
//
//    public function countCoinStats(int $start, int $end, $playerIds)
//    {
//        $query = $this->buildCoinQuery($start, $end, $playerIds);
//        $query->groupBy('player_id');
//
//        $subSql = $query->toSql();
//        $stdClass = DB::table(DB::raw("($subSql) as tab"))
//            ->select(DB::raw('count(*) as total'))
//            ->mergeBindings($query->getQuery())
//            ->get()
//            ->first();
//        return $stdClass->total ?? 0;
//    }
//
//    public function getCoinStatsByPlayerIds(int $start, int $end, $playerIds)
//    {
//        // 金币统计
//        $coinQuery = $this->buildCoinQuery($start, $end, $playerIds);
//        return $coinQuery->groupBy('player_id')->get();
//    }
//
//    private function buildTicketQuery(int $start, int $end, $playerIds): \Illuminate\Database\Eloquent\Builder
//    {
//        // 退票统计
//        $ticketQuery = PlayerCurrencyStatLog::query()
//            ->select(DB::raw('sum(add_value) as output, player_id'))
//            ->where('log_type', PlayerCurrencyStatLog::Ticket);
//
//        if ($start && $end) {
//            $ticketQuery->where('start', '>=', $start)
//                ->where('start', '<', $end);
//        }
//
//        $ticketQuery->whereIn('type', [GameLog::MachineRefundTicket, GameLog::AutoRefundTicket])
//            ->where('player_id', '!=', 0);
//
//        if ($playerIds) {
//            if (is_array($playerIds)){
//                $ticketQuery->whereIn('player_id', $playerIds);
//            } else {
//                $ticketQuery->where('player_id', $playerIds);
//            }
//
//        }
//        return $ticketQuery;
//    }
//
//    public function getTicketStats(int $start, int $end, $playerIds, $offset, $limit)
//    {
//        $ticketQuery = $this->buildTicketQuery($start, $end, $playerIds);
//
//        return $ticketQuery->groupBy('player_id')
//            ->orderBy('output', 'desc')
//            ->offset($offset)
//            ->limit($limit)
//            ->get();
//    }
//
//    public function countTicketStats(int $start, int $end, $playerIds)
//    {
//        $query = $this->buildTicketQuery($start, $end, $playerIds);
//        $query->groupBy('player_id');
//
//        $subSql = $query->toSql();
//        $stdClass = DB::table(DB::raw("($subSql) as tab"))
//            ->select(DB::raw('count(*) as total'))
//            ->mergeBindings($query->getQuery())
//            ->get()
//            ->first();
//        return $stdClass->total ?? 0;
//    }
//
//    public function getTicketStatsByPlayerIds(int $start, int $end, $playerIds)
//    {
//        // 退票统计
//        $ticketQuery = $this->buildTicketQuery($start, $end, $playerIds);
//        return $ticketQuery->groupBy('player_id')->get();
//    }
//
//}
