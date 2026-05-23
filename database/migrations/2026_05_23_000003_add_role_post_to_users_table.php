<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->unsignedBigInteger('post_id')->nullable()->after('role_id');
            $table->boolean('is_active')->default(true)->after('remember_token');

            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['post_id']);
            $table->dropColumn(['role_id', 'post_id', 'is_active']);
        });
    }
};
