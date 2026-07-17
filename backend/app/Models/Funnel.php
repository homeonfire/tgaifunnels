<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funnel extends Model
{
    protected $guarded = []; // Наше разрешение на запись

    // Связь: Воронка принадлежит одному Боту
    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }

    // Связь: У воронки много Шагов (Узлов)
    public function steps()
    {
        return $this->hasMany(Step::class);
    }
}