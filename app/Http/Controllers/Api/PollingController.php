<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class PollingController extends Controller
{
    public function index()
    {
        return $this->data('');
    }
}
