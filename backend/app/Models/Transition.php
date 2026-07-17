<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transition extends Model
{
    protected $guarded = []; // Разрешаем массовое заполнение

    public function isEligible(array $userData): bool
    {
        // Если веток нет, считаем, что стрелка всегда проходима (дефолт)
        if (empty($this->conditions)) return true;

        foreach ($this->conditions as $cond) {
            $varName = $cond['var']; // 'age'
            $operator = $cond['op']; // 'Больше или равно'
            $targetValue = $cond['val']; // 18
            
            $currentValue = $userData[$varName] ?? null;

            if ($currentValue === null) return false;

            switch ($operator) {
                case 'Больше или равно': if (!($currentValue >= $targetValue)) return false; break;
                case 'Меньше (<)':      if (!($currentValue < $targetValue)) return false; break;
                case 'Равно (==)':      if (!($currentValue == $targetValue)) return false; break;
            }
        }
        return true;
    }
}
