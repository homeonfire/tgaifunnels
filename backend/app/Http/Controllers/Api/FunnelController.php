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

        // 2. Получаем текущие шаги из БД, чтобы их обновлять, а не удалять
        $existingSteps = $funnel->steps()->get()->keyBy('id');
        $currentStepIds = $existingSteps->pluck('id');

        // Удаляем ТОЛЬКО связи (edges). Пересоздавать связи безопасно, 
        // так как они не привязаны к сессиям пользователей.
        if ($currentStepIds->isNotEmpty()) {
            Transition::whereIn('from_step_id', $currentStepIds)
                ->orWhereIn('to_step_id', $currentStepIds)
                ->delete();
        }
        
        // 3. Обновляем существующие шаги или создаем новые
        $stepIdMapping = []; 
        $nodesToKeep = []; // Здесь будем хранить ID шагов, которые остались на холсте

        foreach ($request->input('nodes', []) as $node) {
            $frontId = $node['id'];
            
            $stepData = [
                'name' => $node['data']['label'] ?? 'Новый шаг',
                'message_text' => $node['data']['description'] ?? '',
                'use_ai' => $node['data']['useAi'] ?? true, 
                'ai_prompt' => $node['data']['aiPrompt'] ?: ($node['data']['description'] ?? ''),
                'pos_x' => $node['position']['x'],
                'pos_y' => $node['position']['y'],
                'handles' => $node['data']['handles'] ?? null,
                'extracted_variables' => $node['data']['extractedVariables'] ?? null,
            ];

            // Если ID с фронта числовой и такой шаг уже есть в БД — ОБНОВЛЯЕМ его
            if (is_numeric($frontId) && $existingSteps->has($frontId)) {
                $step = $existingSteps[$frontId];
                $step->update($stepData);
            } else {
                // Иначе (это совершенно новый узел, который ты вытянул на холст) — СОЗДАЕМ
                $step = $funnel->steps()->create($stepData);
            }
            
            $nodesToKeep[] = $step->id;
            $stepIdMapping[$frontId] = $step->id; 
        }

        // 4. Удаляем только те шаги, которые ты реально удалил в редакторе (клавишей Delete)
        $stepsToDelete = $currentStepIds->diff($nodesToKeep);
        
        if ($stepsToDelete->isNotEmpty()) {
            // Мягкая защита сессий: если кто-то из клиентов завис на шаге, который ты удалил,
            // мы сбрасываем его current_step_id в null. 
            // При следующем сообщении FunnelEngine мягко перезапустит его с начала воронки.
            \App\Models\ChatSession::whereIn('current_step_id', $stepsToDelete)
                ->update(['current_step_id' => null]);
                
            $funnel->steps()->whereIn('id', $stepsToDelete)->delete();
        }

        // 5. Создаем новые связи
        foreach ($request->input('edges', []) as $edge) {
            $fromId = $stepIdMapping[$edge['source']] ?? null;
            $toId = $stepIdMapping[$edge['target']] ?? null;

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

    return response()->json(['message' => 'Схема успешно сохранена без потери активных сессий']);
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