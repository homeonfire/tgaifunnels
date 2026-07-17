<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transition extends Model
{
    protected $guarded = []; // Разрешаем массовое заполнение

    public function isEligible(array $userData): bool
{
    if (empty($this->conditions)) return true;

    foreach ($this->conditions as $cond) {
        $varName = $cond['variable'] ?? null;
        $operator = $cond['operator'] ?? null;
        $targetValue = $cond['value'] ?? null; // То, что админ выбрал в UI (например, "true")
        
        $currentValue = $userData[$varName] ?? null; // То, что реально лежит в сессии

        // 1. Операторы "Заполнено" / "Не заполнено"
        if ($operator === 'filled' || $operator === 'Заполнено') {
            if (empty($currentValue)) return false;
            continue;
        }
        if ($operator === 'not_filled' || $operator === 'Не заполнено') {
            if (!empty($currentValue)) return false;
            continue;
        }

        // 2. Логика для Boolean (приводим всё к bool)
        if ($operator === 'Равно (==)' || $operator === '==') {
            // Превращаем всё в настоящий boolean (поддерживает "true", "false", 1, 0, true, false)
            $currentBool = filter_var($currentValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$currentValue;
            $targetBool = filter_var($targetValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$targetValue;
            
            if ($currentBool !== $targetBool) return false;
            continue;
        }

        // 3. Сравнение чисел (для остальных операторов)
        $current = (float)($currentValue ?? 0);
        $target = (float)($targetValue ?? 0);

        switch ($operator) {
            case 'Равно (==)': case '==': if (!($current == $target)) return false; break;
            case 'Не равно (!=)': case '!=': if (!($current != $target)) return false; break;
            case 'Больше (>)': case '>': if (!($current > $target)) return false; break;
            case 'Меньше (<)': case '<': if (!($current < $target)) return false; break;
            case 'Больше или равно (>=)': case '>=': if (!($current >= $target)) return false; break;
            case 'Меньше или равно (<=)': case '<=': if (!($current <= $target)) return false; break;
            default:
                \Illuminate\Support\Facades\Log::warning("Unknown operator: {$operator}");
                return false;
        }
    }
    return true;
}

    protected $casts = [
        'conditions' => 'array',
        'condition' => 'array',
    ];
}
