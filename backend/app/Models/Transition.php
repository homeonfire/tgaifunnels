<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transition extends Model
{
    protected $guarded = []; // Разрешаем массовое заполнение

    public function isEligible(array $userData): bool
    {
        // Если условий нет — стрелка проходима всегда
        if (empty($this->conditions)) return true;

        foreach ($this->conditions as $cond) {
            $varName = $cond['var'];
            $operator = $cond['op'];
            $targetValue = $cond['val'];
            
            $currentValue = $userData[$varName] ?? null;

            // Если данных нет, стрелка не подходит
            if ($currentValue === null) return false;

            // ПРИНУДИТЕЛЬНОЕ ПРИВЕДЕНИЕ К ЧИСЛУ
            $current = (float)$currentValue;
            $target = (float)$targetValue;

            // ЛОГГИРОВАНИЕ ДЛЯ ОТЛАДКИ (смотри в storage/logs/laravel.log)
            \Illuminate\Support\Facades\Log::info("Check: {$current} {$operator} {$target}");

            switch ($operator) {
                case 'Больше или равно': 
                    if (!($current >= $target)) return false; 
                    break;
                case 'Меньше (<)':      
                    if (!($current < $target)) return false; 
                    break;
                case 'Равно (==)':      
                    if (!($current == $target)) return false; 
                    break;
            }
        }
        return true;
    }

    protected $casts = [
        'conditions' => 'array',
    ];
}
