<?php

namespace App\Exceptions;

use App\Exceptions\Api\ErrCode;
use Illuminate\Support\Facades\Log;

class GameApiException extends \Exception
{
    public function report()
    {
        Log::info($this->getTraceAsString());
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = response()->json([
            'code' => ErrCode::GameApiFail,
            'message' => trans('err.game_call_failed')
        ]);
        $response->setStatusCode(200);
        return $response;
    }
}
