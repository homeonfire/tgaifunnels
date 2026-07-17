<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('message_text')->nullable();
            $table->float('pos_x')->default(0);
            $table->float('pos_y')->default(0);
            $table->boolean('use_ai')->default(false);
            $table->text('ai_prompt')->nullable();
            $table->json('extracted_variables')->nullable(); // Хранит JSON Schema
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
