<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use App\Models\Transition; // <-- Не забудь добавить этот импорт
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    
    DB::transaction(function () use ($funnel, $request) {
        
        // 1. Обновляем основные данные воронки
        $funnel->update([
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active'),
            'bot_id' => $request->input('bot_id'),
        ]);

        // 2. Получаем ID всех текущих шагов
        $currentStepIds = $funnel->steps()->pluck('id');

        if ($currentStepIds->isNotEmpty()) {
            // ПРЯМОЕ УДАЛЕНИЕ: Сначала удаляем все связи, 
            // где 'from' ИЛИ 'to' ссылается на наши шаги.
            // Это обходит ограничения внешних ключей.
            Transition::whereIn('from_step_id', $currentStepIds)
                ->orWhereIn('to_step_id', $currentStepIds)
                ->delete();

            // Теперь, когда связи удалены, шаги можно удалять без ошибок
            $funnel->steps()->delete();
        }
        
        // 3. Создаем новые шаги
        $stepIdMapping = []; 

        foreach ($request->input('nodes', []) as $node) {
            $step = $funnel->steps()->create([
                'name' => $node['data']['label'] ?? 'Новый шаг',
                'message_text' => $node['data']['description'] ?? '',
                'use_ai' => $node['data']['useAi'] ?? false,
                'ai_prompt' => $node['data']['aiPrompt'] ?? '',
                'pos_x' => $node['position']['x'],
                'pos_y' => $node['position']['y'],
                'handles' => $node['data']['handles'] ?? null,
                'extracted_variables' => $node['data']['extractedVariables'] ?? null,
            ]);
            
            $stepIdMapping[$node['id']] = $step->id; 
        }

        // 4. Создаем новые связи
        foreach ($request->input('edges', []) as $edge) {
            $fromId = $stepIdMapping[$edge['source']] ?? null;
            $toId = $stepIdMapping[$edge['target']] ?? null;

            // Если оба ID существуют (шаги были успешно созданы)
            if ($fromId && $toId) {
                Transition::create([
                    'from_step_id' => $fromId,
                    'to_step_id' => $toId,
                    'source_handle' => $edge['sourceHandle'] ?? 'default',
                    'conditions' => $edge['conditions'] ?? null,
                ]);
            }
        }
    });

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