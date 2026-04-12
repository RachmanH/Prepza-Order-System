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
        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('external_status', ['not_set', 'received', 'processing', 'done'])
                ->default('not_set')
                ->after('status')
                ->index();
            $table->string('external_reference')->nullable()->after('external_status');
            $table->text('external_note')->nullable()->after('external_reference');
            $table->timestamp('external_updated_at')->nullable()->after('external_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'external_status',
                'external_reference',
                'external_note',
                'external_updated_at',
            ]);
        });
    }
};
