<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;

class BotController extends Controller
{
    public function index()
    {
        // Отдаем только id и name, чтобы не грузить лишние данные
        return response()->json(Bot::select('id', 'name')->get());
    }
}