<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FunnelEngine;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{
    protected FunnelEngine $engine;

    public function __construct(FunnelEngine $engine)
    {
        $this->engine = $engine;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'bot_id' => 'required|integer',
            'client_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $reply = $this->engine->handleMessage(
            $request->input('bot_id'),
            $request->input('client_id'),
            $request->input('message')
        );

        // Достаем актуальный ID шага после обработки сообщения
        // Достаем актуальный ID шага
        $session = \App\Models\ChatSession::where('bot_id', $request->bot_id)
                    ->where('client_id', $request->client_id)->first();

        return response()->json([
            'reply' => $reply,
            'current_step_id' => $session ? $session->current_step_id : null
        ]);
    }
}