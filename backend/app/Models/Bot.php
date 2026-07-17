<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $guarded = [];

    // Связь с таблицей ключей
    public function aiKey()
    {
        return $this->belongsTo(AiKey::class, 'ai_key_id');
    }

    public function funnels()
    {
        return $this->hasMany(Funnel::class);
    }
}