<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_step_id')->constrained('steps')->cascadeOnDelete();
            $table->foreignId('to_step_id')->constrained('steps')->cascadeOnDelete();
            $table->string('source_handle')->nullable(); 
            $table->string('condition')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transitions');
    }
};
