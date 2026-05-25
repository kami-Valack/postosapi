<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->string('status')->default('processing'); // processing | completed | partial
            $table->unsignedSmallInteger('operations_total')->default(0);
            $table->unsignedSmallInteger('operations_applied')->default(0);
            $table->unsignedSmallInteger('operations_conflicted')->default(0);
            $table->unsignedSmallInteger('operations_rejected')->default(0);
            $table->timestamp('client_batch_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
        });

        Schema::create('offline_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offline_sync_batch_id')->constrained('offline_sync_batches')->cascadeOnDelete();
            $table->uuid('client_operation_id');
            $table->string('operation_type');
            $table->json('payload');
            $table->timestamp('client_timestamp');
            $table->string('status'); // applied | conflict | rejected | duplicate
            $table->text('conflict_reason')->nullable();
            $table->json('server_result')->nullable();
            $table->timestamps();

            $table->unique(['offline_sync_batch_id', 'client_operation_id'], 'sync_ops_batch_client_unique');
            $table->index('client_operation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_operations');
        Schema::dropIfExists('offline_sync_batches');
    }
};
