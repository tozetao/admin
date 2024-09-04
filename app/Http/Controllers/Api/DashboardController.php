<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user1 = Auth::user();

        $user2 = Auth::user();

        Log::info($user1);
        Log::info($user2);

        $startDate = $request->get('start');
        $endDate = $request->get('end');

        $start = parse_from_date($startDate, true);
        $end   = parse_to_date($endDate, true);

        return $this->data([]);
    }
}
