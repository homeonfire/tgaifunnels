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
            \Illuminate\Support\Facades\Log::warning("Сообщение проигнорировано: нет активной воронки с ID {$funnelId}");
            return "В данный момент бот не настроен.";
        }

        // 2. Сессия
        $session = \App\Models\ChatSession::firstOrCreate(
            ['client_id' => $clientId],
            [
                'funnel_id' => $funnel->id,  
                'bot_id' => $funnel->bot_id, 
                'user_data' => []
            ]
        );

        // ЖЕЛЕЗОБЕТОННОЕ извлечение истории (спасает от любых проблем с $casts)
        $history = $session->history;
        if (is_string($history)) {
            $history = json_decode($history, true) ?? [];
        }
        if (!is_array($history)) {
            $history = [];
        }

        // 3. Установка первого шага ИЛИ жесткий сброс воронки
        if (!$session->current_step_id || !$session->currentStep) { 
            
            $firstStep = \App\Models\Step::where('funnel_id', $funnel->id)
                ->whereNotIn('id', \App\Models\Transition::pluck('to_step_id'))
                ->first();

            if (!$firstStep) {
                return "Ошибка воронки: Не найден стартовый этап.";
            }

            $session->current_step_id = $firstStep->id;
            $session->user_data = [];
            $history = []; // Очищаем историю только если начали воронку с самого нуля
            $session->history = $history;
            $session->save();
        }

        $currentStep = $session->fresh()->currentStep;

        // 4. Отправляем запрос ИИ
        $aiResponse = $this->aiService->processMessage(
            $currentStep, 
            $message, 
            $session->user_data ?? [],
            $history
        );
        
        // 5. Безопасное сохранение переменных
        $currentData = $session->user_data ?? [];
        if (!empty($aiResponse['extracted_data'])) {
            $extracted = array_filter($aiResponse['extracted_data'], function($val) {
                return $val !== null && $val !== '';
            });
            $currentData = array_merge($currentData, $extracted);
            $session->user_data = $currentData;
        }

        // 6. НАКАПЛИВАЕМ ИСТОРИЮ (теперь она точно не потеряется)
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $aiResponse['reply'] ?? ''];

        // Ограничиваем историю 20 последними сообщениями
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // 7. ПРОВЕРКА ПРОХОЖДЕНИЯ ШАГА (State Lock)
        $isStepCompleted = true;
        if (!empty($currentStep->extracted_variables)) {
            foreach ($currentStep->extracted_variables as $var) {
                if (empty($currentData[$var['name']])) {
                    $isStepCompleted = false;
                    break;
                }
            }
        }

        // 8. Переход на следующий этап
        if ($isStepCompleted) {
            $transitions = \App\Models\Transition::where('from_step_id', $currentStep->id)->get();
            
            foreach ($transitions as $transition) {
                if ($transition->isEligible($currentData)) {
                    
                    // Обновляем текущий шаг в сессии
                    $session->current_step_id = $transition->to_step_id;
                    $session->save(); 
                    
                    $nextStep = $session->fresh()->currentStep; 
                    
                    // Берем то, что хотел ответить первый этап
                    $draftReply = $aiResponse['reply'] ?? '';
                    
                    // Скрытый промпт для второго этапа: заставляем его "причесать" текст
                    $hiddenPrompt = "[СИСТЕМНОЕ СООБЩЕНИЕ]: Произошел автоматический переход на твой этап. Предыдущий этап подготовил такой ответ: '{$draftReply}'. Перепиши этот ответ: оставь из него суть (если там был ответ на вопрос юзера), убери лишние дежурные вопросы вроде 'Чем могу помочь?', и ОБЯЗАТЕЛЬНО плавно переведи тему на свою системную задачу. Ответь от первого лица одним красивым сообщением.";
                    
                    $nextStepAiResponse = $this->aiService->processMessage(
                        $nextStep, 
                        $hiddenPrompt, 
                        $currentData, 
                        $history
                    );
                    
                    $finalReply = $nextStepAiResponse['reply'] ?? '...';

                    // Сохраняем в историю ТОЛЬКО финальный красивый ответ, 
                    // чтобы бот не путался в своих "черновиках"
                    $history[] = ['role' => 'assistant', 'content' => $finalReply];
                    $session->history = $history;
                    $session->save();
                    
                    return $finalReply; 
                }
            }
        }

        // 9. Если перехода нет (остались на том же шаге) — просто сохраняем текущий ответ
        $finalReply = $aiResponse['reply'] ?? 'Извините, я не понял ваш запрос.';
        $history[] = ['role' => 'assistant', 'content' => $finalReply];
        $session->history = $history;
        $session->save();

        return $finalReply;
    }
}