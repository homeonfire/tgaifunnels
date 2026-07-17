<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            // Добавляем внешний ключ с каскадным обнулением при удалении ключа
            $table->foreignId('ai_key_id')->nullable()->after('telegram_token')->constrained('ai_keys')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropForeign(['ai_key_id']);
            $table->dropColumn('ai_key_id');
        });
    }
};
