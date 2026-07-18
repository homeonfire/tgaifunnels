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
            'funnel_id' => 'required|integer', // <-- Теперь требуем ID воронки
            'bot_id' => 'nullable|integer',    // Оставим для совместимости сессий
            'client_id' => 'required|string',
            'message' => 'required|string',
        ]);

        // Передаем funnel_id в движок вместо bot_id
        $reply = $this->engine->handleMessage(
            $request->input('funnel_id'), 
            $request->input('client_id'),
            $request->input('message')
        );

        // client_id уникален для каждого запуска симулятора (ты генеришь его через Math.random)
        // Поэтому искать сессию можно просто по нему
        $session = \App\Models\ChatSession::where('client_id', $request->client_id)->first();

        return response()->json([
            'reply' => $reply,
            'current_step_id' => $session ? $session->current_step_id : null
        ]);
    }
}