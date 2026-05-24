<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('tipo')->default('combustivel')->after('is_active');
            $table->string('preco')->nullable()->after('tipo');
            $table->string('preco_premium')->nullable()->after('preco');
            $table->string('combustivel')->nullable()->after('preco_premium');
            $table->string('status')->default('aberto')->after('combustivel');
            $table->boolean('hours_24')->default(false)->after('status');
            $table->string('image')->nullable()->after('hours_24');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'tipo',
                'preco',
                'preco_premium',
                'combustivel',
                'status',
                'hours_24',
                'image',
            ]);
        });
    }
};
