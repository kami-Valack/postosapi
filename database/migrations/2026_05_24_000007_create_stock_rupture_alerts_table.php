<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_rupture_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->nullOnDelete();
            $table->decimal('current_quantity', 20, 4);
            $table->decimal('avg_hourly_consumption', 20, 4)->nullable();
            $table->decimal('hours_until_rupture', 10, 2)->nullable();
            $table->timestamp('predicted_rupture_at')->nullable();
            $table->string('severity'); // warning | critical
            $table->string('status')->default('active'); // active | acknowledged | resolved
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'status']);
            $table->index(['post_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_rupture_alerts');
    }
};
