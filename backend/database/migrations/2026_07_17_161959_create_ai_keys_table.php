<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название (например, "Мой рабочий DeepSeek")
            $table->string('provider')->default('deepseek'); // Провайдер (deepseek, openai и тд)
            $table->string('token'); // Сам API ключ
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_keys');
    }
};