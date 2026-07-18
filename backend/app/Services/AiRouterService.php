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
        // Достаем ключ через новую связь: Шаг -> Воронка -> Бот -> AiKey
        $bot = $step->funnel->bot;
        $apiKey = $bot?->aiKey?->token; // Безопасное извлечение ключа

        if (!$apiKey) {
            Log::warning('Попытка вызова AI без привязанного API ключа', ['bot_id' => $bot?->id]);
            return [
                'reply' => 'Ошибка: Бот не привязан к API ключу нейросети.',
                'extracted_data' => []
            ];
        }

        $systemPrompt = $step->ai_prompt ?: 'Ты полезный ИИ-ассистент.';
        $systemPrompt .= "\n\n=== ВАЖНЫЕ СИСТЕМНЫЕ ИНСТРУКЦИИ ===\n";
        $systemPrompt .= "Ты ДОЛЖЕН вернуть ответ СТРОГО в формате валидного JSON.\n";
        $systemPrompt .= "Твой JSON должен содержать ключ 'reply' с твоим текстовым ответом.\n";

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

        if (!empty($currentSessionData)) {
            $messages[] = [
                'role' => 'system', 
                'content' => 'Известные данные: ' . json_encode($currentSessionData, JSON_UNESCAPED_UNICODE)
            ];
        }

        // --- НОВЫЙ БЛОК: Вливаем историю предыдущего общения на этом шаге ---
        foreach ($history as $hMsg) {
            $messages[] = [
                'role' => $hMsg['role'],
                'content' => $hMsg['content']
            ];
        }

        // Добавляем текущее (самое свежее) сообщение юзера
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $bot->ai_model ?: 'deepseek-chat', // Берем модель из настроек бота
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

            if ($response->failed()) {
                Log::error('DeepSeek API Error', ['body' => $response->body()]);
                throw new \Exception('Ошибка API DeepSeek');
            }

            $result = $response->json();
            $aiContent = $result['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($aiContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['reply' => 'Извините, не удалось обработать формат ответа.', 'extracted_data' => []];
            }

            $reply = $parsed['reply'] ?? '';
            unset($parsed['reply']);

            return ['reply' => $reply, 'extracted_data' => $parsed];

        } catch (\Exception $e) {
            Log::error('AiRouterService Exception', ['message' => $e->getMessage()]);
            return ['reply' => 'Техническая заминка.', 'extracted_data' => []];
        }
    }
}