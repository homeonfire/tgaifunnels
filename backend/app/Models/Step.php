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
}