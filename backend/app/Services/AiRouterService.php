<?php

namespace App\Services;

use App\Models\Step;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRouterService
{
    protected string $baseUrl = 'https://api.deepseek.com';

    public function processMessage(Step $step, string $userMessage, array $currentSessionData = [], array $history = [])
    {
        $bot = $step->funnel->bot;
        $apiKey = $bot?->aiKey?->token;

        if (!$apiKey) {
            \Illuminate\Support\Facades\Log::warning('Попытка вызова AI без привязанного API ключа', ['bot_id' => $bot?->id]);
            return [
                'reply' => 'Ошибка: Бот не привязан к API ключу нейросети.',
                'extracted_data' => []
            ];
        }

        // --- 1. СБОРКА СИСТЕМНОГО ПРОМПТА ---
        $systemPrompt = "";

        // Глобальный контекст (если есть)
        if (!empty($bot->global_context)) {
            $systemPrompt .= "=== ГЛОБАЛЬНАЯ ИНСТРУКЦИЯ И КОНТЕКСТ ===\n";
            $systemPrompt .= $bot->global_context . "\n\n";
        }

        // Локальная задача шага
        $systemPrompt .= "=== ЗАДАЧА ТЕКУЩЕГО ЭТАПА ===\n";
        $systemPrompt .= $step->ai_prompt ?: 'Действуй по глобальным инструкциям.';

        // Жесткие правила формата
        $systemPrompt .= "\n\n=== ФОРМАТ ОТВЕТА ===\n";
        $systemPrompt .= "Ты ДОЛЖЕН вернуть ответ СТРОГО в формате валидного JSON.\n";
        $systemPrompt .= "Твой JSON должен содержать ключ 'reply' с твоим текстовым ответом.\n";

        // Инструкция для переменных
        if (!empty($step->extracted_variables)) {
            $systemPrompt .= "Также извлеки из сообщения пользователя следующие данные и добавь их ключами в JSON:\n";
            foreach ($step->extracted_variables as $var) {
                $type = $var['type'] ?? 'string';
                $desc = $var['description'] ?? '';
                $systemPrompt .= "- \"{$var['name']}\" ({$type}): {$desc}\n";
            }
            $systemPrompt .= "Если данных для переменной пока нет, передай null.\n";
        }

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // --- 2. ДОБАВЛЕНИЕ УЖЕ ИЗВЕСТНЫХ ДАННЫХ ---
        if (!empty($currentSessionData)) {
            $messages[] = [
                'role' => 'system', 
                'content' => 'Уже собранные данные о пользователе: ' . json_encode($currentSessionData, JSON_UNESCAPED_UNICODE)
            ];
        }

        // --- 3. ВЛИВАНИЕ ИСТОРИИ ДИАЛОГА ---
        foreach ($history as $hMsg) {
            $messages[] = [
                'role' => $hMsg['role'],
                'content' => $hMsg['content']
            ];
        }

        // --- 4. ДОБАВЛЕНИЕ НОВОГО СООБЩЕНИЯ ---
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // --- ОТПРАВКА ЗАПРОСА ---
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $bot->ai_model ?: 'deepseek-chat',
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('DeepSeek API Error', ['body' => $response->body()]);
                throw new \Exception('Ошибка API DeepSeek');
            }

            $result = $response->json();
            $aiContent = $result['choices'][0]['message']['content'] ?? '{}';
            
            // Очистка от маркдауна и мусора (защита от ```json ... ```)
            $start = strpos($aiContent, '{');
            $end = strrpos($aiContent, '}');

            if ($start !== false && $end !== false) {
                $aiContent = substr($aiContent, $start, $end - $start + 1);
            }

            $parsed = json_decode($aiContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::error('AI JSON Decode Error', [
                    'cleaned_content' => $aiContent,
                    'error' => json_last_error_msg()
                ]);
                return ['reply' => 'Извините, не удалось обработать формат ответа.', 'extracted_data' => []];
            }

            $reply = $parsed['reply'] ?? '';
            unset($parsed['reply']);

            return ['reply' => $reply, 'extracted_data' => $parsed];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AiRouterService Exception', ['message' => $e->getMessage()]);
            return ['reply' => 'Техническая заминка.', 'extracted_data' => []];
        }
    }
}