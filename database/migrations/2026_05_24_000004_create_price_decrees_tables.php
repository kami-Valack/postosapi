<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_decrees', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('fuel_type_id')->nullable()->constrained('fuel_types')->nullOnDelete();
            $table->string('preco');
            $table->string('preco_premium')->nullable();
            $table->timestamp('effective_from');
            $table->timestamp('confirmation_deadline')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('effective_from');
            $table->index('confirmation_deadline');
        });

        Schema::create('post_price_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('price_decree_id')->constrained('price_decrees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('confirmed_at');
            $table->string('motivo_atraso')->nullable();
            $table->boolean('was_late')->default(false);
            $table->timestamps();

            $table->unique(['post_id', 'price_decree_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_price_confirmations');
        Schema::dropIfExists('price_decrees');
    }
};
