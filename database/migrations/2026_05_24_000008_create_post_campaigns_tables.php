<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->unsignedInteger('radius_meters');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->unsignedBigInteger('conversions_count')->default(0);
            $table->text('feedback_qualitativo')->nullable();
            $table->timestamp('feedback_submitted_at')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('campaign_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_campaign_id')->constrained('post_campaigns')->cascadeOnDelete();
            $table->string('event_type'); // view | click | conversion
            $table->string('client_user_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->timestamps();

            $table->index(['post_campaign_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_interactions');
        Schema::dropIfExists('post_campaigns');
    }
};
