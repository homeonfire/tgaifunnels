<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use App\Models\Transition; // <-- Не забудь добавить этот импорт
use Illuminate\Http\Request;

class FunnelController extends Controller
{
    public function index()
    {
        $funnels = Funnel::with('bot:id,name')
            ->withCount('steps as nodes_count')
            ->get();

        return response()->json($funnels);
    }

    // НОВЫЙ МЕТОД
    public function show($id)
    {
        // Достаем воронку вместе со всеми ее шагами
        $funnel = Funnel::with('steps')->findOrFail($id);
        
        // Находим все связи (transitions), исходящие из этих шагов
        $stepIds = $funnel->steps->pluck('id');
        $transitions = Transition::whereIn('from_step_id', $stepIds)->get();

        return response()->json([
            'funnel' => $funnel,
            'transitions' => $transitions
        ]);
    }

    // НОВЫЙ МЕТОД: Сохранение всей схемы холста
    public function updateSchema(Request $request, $id)
    {
        $funnel = Funnel::findOrFail($id);
        
        // 1. Обновляем основные данные воронки
        $funnel->update([
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active'),
            'bot_id' => $request->input('bot_id'), // <-- ДОБАВИЛИ ЭТУ СТРОЧКУ
        ]);

        // 2. Обновляем узлы (Шаги)
        // Для простоты сейчас мы просто удаляем старые и записываем новые. 
        // В продакшене лучше делать upsert, чтобы не ломать статистику, но для старта это идеально.
        $funnel->steps()->delete(); 
        
        $stepIdMapping = []; // Массив для подмены временных ID с фронта на реальные ID базы

        foreach ($request->input('nodes', []) as $node) {
            $step = $funnel->steps()->create([
                'name' => $node['data']['label'] ?? 'Новый шаг',
                'message_text' => $node['data']['description'] ?? '',
                'use_ai' => $node['data']['useAi'] ?? false,
                'ai_prompt' => $node['data']['aiPrompt'] ?? '',
                'pos_x' => $node['position']['x'],
                'pos_y' => $node['position']['y'],
            ]);
            
            // Запоминаем, какой ID (например 'temp_123') превратился в реальный ID (например 15)
            $stepIdMapping[$node['id']] = $step->id; 
        }

        // 3. Обновляем связи (Переходы)
        $stepIds = $funnel->steps->pluck('id');
        Transition::whereIn('from_step_id', $stepIds)->delete(); // Удаляем старые связи

        foreach ($request->input('edges', []) as $edge) {
            // Ищем реальные ID в нашем маппинге (или оставляем как есть, если они не менялись)
            $fromId = $stepIdMapping[$edge['source']] ?? $edge['source'];
            $toId = $stepIdMapping[$edge['target']] ?? $edge['target'];

            Transition::create([
                'from_step_id' => $fromId,
                'to_step_id' => $toId,
                'source_handle' => $edge['sourceHandle'] ?? 'default',
            ]);
        }

        return response()->json(['message' => 'Схема успешно сохранена']);
    }

    // НОВЫЙ МЕТОД: Создание пустой воронки
    public function store(Request $request)
    {
        // Берем первого бота из базы, чтобы не было ошибки пустого bot_id
        $bot = \App\Models\Bot::first();

        // Создаем саму воронку
        $funnel = Funnel::create([
            'name' => 'Новая воронка',
            'is_active' => false,
            'bot_id' => $bot ? $bot->id : null, // Привязываем к боту
        ]);

        // Сразу создаем для неё стартовый узел
        $funnel->steps()->create([
            'name' => 'Start (Начало)',
            'message_text' => 'Привет! Чем могу помочь?',
            'pos_x' => 250,
            'pos_y' => 250,
        ]);

        return response()->json($funnel);
    }
}