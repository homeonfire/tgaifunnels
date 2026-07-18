<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bots', function (Blueprint $table) {
            // Добавляем поле типа text, так как промпт может быть объемным
            $table->text('global_context')->nullable()->after('name'); 
        });
    }

    public function down()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn('global_context');
        });
    }
};
