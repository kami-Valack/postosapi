<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_service', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('service_id');
            $table->string('motivo_desativacao')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('post_service', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'motivo_desativacao']);
        });
    }
};
