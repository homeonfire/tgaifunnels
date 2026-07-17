<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('transitions', function (Blueprint $table) {
        // Добавляем поле для хранения JSON с условиями
        $table->json('conditions')->nullable();
    });
}

public function down(): void
{
    Schema::table('transitions', function (Blueprint $table) {
        $table->dropColumn('conditions');
    });
}
};
