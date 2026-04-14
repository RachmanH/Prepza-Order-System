<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_trends', function (Blueprint $table): void {
            $table->enum('gender_target', ['male', 'female', 'all'])->default('all')->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('queue_trends', function (Blueprint $table): void {
            $table->dropColumn('gender_target');
        });
    }
};
