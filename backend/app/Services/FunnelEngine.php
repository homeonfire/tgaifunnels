<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\ChatSession;
use App\Models\Funnel;
use App\Models\Step;
use App\Models\Transition;
use Illuminate\Support\Facades\Log;

class FunnelEngine
{
    protected AiRouterService $aiService;

    public function __construct(AiRouterService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function handleMessage(int $funnelId, string $clientId, string $message): string
    {
        // 1. Ищем активную воронку
        $funnel = \App\Models\Funnel::findOrFail($funnelId);
        
        if (!$funnel) {
            Log::warning("Сообщение проигнорировано: нет активной воронки для бота {$botId}");
            return "В данный момент бот не настроен.";
        }

        // 2. Сессия
        $session = \App\Models\ChatSession::firstOrCreate(
            ['client_id' => $clientId],
            [
                'funnel_id' => $funnel->id,  // <--- ВОТ ЭТА СТРОЧКА НУЖНА
                'bot_id' => $funnel->bot_id, 
                'user_data' => []
            ]
        );

        // 3. Установка первого шага ИЛИ сброс, если текущий шаг был удален при редактировании
        // Добавили проверку !$session->currentStep
        if (!$session->current_step_id || !$session->currentStep) { 
            
            $firstStep = Step::where('funnel_id', $funnel->id)
                ->whereNotIn('id', Transition::pluck('to_step_id'))
                ->first();

            if (!$firstStep) {
                return "Ошибка воронки: Не найден стартовый этап.";
            }

            // Обновляем сессию: ставим новый стартовый шаг
            $session->update([
                'current_step_id' => $firstStep->id,
                // Опционально: можно сбросить user_data, так как воронка началась заново
                'user_data' => [] 
            ]);
        }

        // Теперь мы на 100% уверены, что шаг существует
        $currentStep = $session->currentStep;

        // 4. Отправляем запрос ИИ
        // --- ЛОГИРУЕМ ТО, ЧТО ОТПРАВЛЯЕМ ---
        \Illuminate\Support\Facades\Log::info("========== ЗАПРОС К ИИ ==========");
        \Illuminate\Support\Facades\Log::info("Шаг ID: " . $currentStep->id);
        \Illuminate\Support\Facades\Log::info("Промпт шага (ai_prompt): " . ($currentStep->ai_prompt ?: 'ПУСТО'));
        \Illuminate\Support\Facades\Log::info("Сообщение юзера: " . $message);
        \Illuminate\Support\Facades\Log::info("Текущие user_data: " . json_encode($session->user_data ?? [], JSON_UNESCAPED_UNICODE));

        $aiResponse = $this->aiService->processMessage($currentStep, $message, $session->user_data ?? []);

        // --- ЛОГИРУЕМ ТО, ЧТО ВЕРНУЛОСЬ ---
        \Illuminate\Support\Facades\Log::info("========== ОТВЕТ ОТ ИИ ==========");
        \Illuminate\Support\Facades\Log::info("Сырой массив ответа: " . json_encode($aiResponse, JSON_UNESCAPED_UNICODE));
        \Illuminate\Support\Facades\Log::info("=================================");
        
        // 5. Безопасное сохранение переменных
        $currentData = $session->user_data ?? [];
        if (!empty($aiResponse['extracted_data'])) {
            // Очищаем null значения, чтобы ИИ не затер уже собранные данные
            $extracted = array_filter($aiResponse['extracted_data'], function($val) {
                return $val !== null && $val !== '';
            });
            
            $currentData = array_merge($currentData, $extracted);
            $session->update(['user_data' => $currentData]);
        }

        // 6. ПРОВЕРКА ПРОХОЖДЕНИЯ ШАГА (State Lock)
        $isStepCompleted = true;
        
        if (!empty($currentStep->extracted_variables)) {
            foreach ($currentStep->extracted_variables as $var) {
                $varName = $var['name'];
                // Если переменной нет в собранных данных — цель не достигнута
                if (empty($currentData[$varName])) {
                    $isStepCompleted = false;
                    break;
                }
            }
        }

        // 7. Переход на следующий этап
        if ($isStepCompleted) {
            $transitions = Transition::where('from_step_id', $currentStep->id)->get();
            
            // ЛОГ ДЛЯ ОТЛАДКИ: сколько стрелок мы нашли?
            Log::info("Engine: Ищем переходы для шага {$currentStep->id}. Найдено: " . $transitions->count());
            
            foreach ($transitions as $transition) {
                Log::info("Engine: Проверяем переход к шагу {$transition->to_step_id}. Условия: " . json_encode($transition->conditions));
                
                if ($transition->isEligible($currentData)) {
                    Log::info("Engine: Условие подошло! Переходим к {$transition->to_step_id}");
                    
                    $session->update(['current_step_id' => $transition->to_step_id]);
                    
                    $nextStep = $session->fresh()->currentStep; 
                    $nextStepAiResponse = $this->aiService->processMessage($nextStep, "Приветствие", $session->user_data);
                    
                    return $nextStepAiResponse['reply']; 
                }
            }
        }

        return $aiResponse['reply'] ?? 'Извините, я не понял ваш запрос.';
    }
}