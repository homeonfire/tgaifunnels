<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKey extends Model
{
    protected $guarded = [];

    // Один ключ может использоваться в нескольких ботах
    public function bots()
    {
        return $this->hasMany(Bot::class, 'ai_key_id');
    }
}