<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bot;
use App\Models\Funnel;
use App\Models\Step;
use App\Models\Transition;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создаем тестового бота
        $bot = Bot::create([
            'name' => 'Demo AI Router',
            'telegram_token' => '1234567890:ABCdefGHIjklMNOpqrsTUVwxyz',
            'webhook_url' => 'https://api.example.com/webhook/telegram'
        ]);

        // 2. Создаем воронку для этого бота
        $funnel = Funnel::create([
            'bot_id' => $bot->id,
            'name' => 'Lead Qualification',
            'is_active' => true
        ]);

        // 3. Создаем стартовый шаг (Приветствие)
        $step1 = Step::create([
            'funnel_id' => $funnel->id,
            'name' => 'Start (Приветствие)',
            'message_text' => 'Привет! Расскажи немного о своем проекте, и я подскажу, как мы можем помочь.',
            'pos_x' => 100,
            'pos_y' => 200,
            'use_ai' => false
        ]);

        // 4. Создаем шаг с искусственным интеллектом
        $step2 = Step::create([
            'funnel_id' => $funnel->id,
            'name' => 'AI Qualification',
            'pos_x' => 500,
            'pos_y' => 200,
            'use_ai' => true,
            'ai_prompt' => 'Ты квалификатор лидов. Извлеки из сообщения пользователя его нишу и бюджет.',
            'extracted_variables' => json_encode([
                'niche' => 'string',
                'budget' => 'number'
            ])
        ]);

        // 5. Создаем связь (Transition) между ними
        Transition::create([
            'from_step_id' => $step1->id,
            'to_step_id' => $step2->id,
            'source_handle' => 'default',
            'condition' => 'always',
        ]);
    }
}