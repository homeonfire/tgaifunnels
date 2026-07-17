<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function index()
    {
        // Отдаем ботов сразу с привязанными ключами
        return response()->json(Bot::with('aiKey')->latest()->get());
    }

    public function store(Request $request)
    {
        $bot = Bot::create($request->only(['name', 'telegram_token', 'ai_key_id']));
        return response()->json($bot->load('aiKey'), 201);
    }

    public function update(Request $request, $id)
    {
        $bot = Bot::findOrFail($id);
        $bot->update($request->only(['name', 'telegram_token', 'ai_key_id']));
        return response()->json($bot->load('aiKey'));
    }

    public function destroy($id)
    {
        Bot::destroy($id);
        return response()->json(['message' => 'Бот удален']);
    }
}