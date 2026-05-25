<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('post_fuel_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('fuel_type_id')->constrained('fuel_types')->cascadeOnDelete();
            $table->string('availability')->default('em_stock'); // em_stock | fora_stock
            $table->string('motivo_fora_stock')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'fuel_type_id']);
            $table->index(['post_id', 'availability']);
        });

        Schema::create('fuel_availability_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_fuel_availability_id')->constrained('post_fuel_availabilities')->cascadeOnDelete();
            $table->string('old_availability')->nullable();
            $table->string('new_availability');
            $table->string('motivo_fora_stock')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_availability_histories');
        Schema::dropIfExists('post_fuel_availabilities');
        Schema::dropIfExists('fuel_types');
    }
};
