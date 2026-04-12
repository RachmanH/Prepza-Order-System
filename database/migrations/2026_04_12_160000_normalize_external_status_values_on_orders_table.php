<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'external_status')) {
            return;
        }

        DB::table('orders')
            ->whereNull('external_status')
            ->orWhereIn('external_status', ['not_set', 'received'])
            ->update(['external_status' => 'waiting']);

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY external_status ENUM('waiting','processing','done') NOT NULL DEFAULT 'waiting'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'external_status')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY external_status ENUM('not_set','received','processing','done') NOT NULL DEFAULT 'not_set'");
        }

        DB::table('orders')
            ->where('external_status', 'waiting')
            ->update(['external_status' => 'not_set']);
    }
};
