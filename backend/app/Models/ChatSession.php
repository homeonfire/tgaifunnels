<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'user_data' => 'array',
        ];
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }

    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(Step::class, 'current_step_id');
    }
}