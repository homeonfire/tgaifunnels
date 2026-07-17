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

        return response()->json(['reply' => $reply]);
    }
}