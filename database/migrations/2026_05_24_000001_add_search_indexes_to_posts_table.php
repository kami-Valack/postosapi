<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['is_active', 'name'], 'posts_active_name_index');
            $table->index(['is_active', 'status'], 'posts_active_status_index');
            $table->index('combustivel', 'posts_combustivel_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_active_name_index');
            $table->dropIndex('posts_active_status_index');
            $table->dropIndex('posts_combustivel_index');
        });
    }
};
