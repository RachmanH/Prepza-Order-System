<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'external_reference')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('external_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'external_reference')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('external_reference')->nullable()->after('external_status');
        });
    }
};
