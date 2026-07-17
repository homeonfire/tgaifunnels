<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    // Отключаем защиту, чтобы можно было сохранять любые поля
    protected $guarded = []; 

    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }

    protected function casts(): array
    {
        return [
            'use_ai' => 'boolean',
            'handles' => 'array',
            'extracted_variables' => 'array', // <-- Добавили эту строку
        ];
    }
}