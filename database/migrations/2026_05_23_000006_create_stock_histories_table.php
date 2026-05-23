<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->decimal('old_quantity', 20, 4)->nullable();
            $table->decimal('new_quantity', 20, 4)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('justificativa_ajuste')->nullable();
            $table->timestamps();

            $table->foreign('stock_id')->references('id')->on('stocks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
