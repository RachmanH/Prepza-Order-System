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
        Schema::table('menus', function (Blueprint $table) {
            // Add category_id column if it doesn't exist
            if (!Schema::hasColumn('menus', 'category_id')) {
                $table->foreignId('category_id')->after('slug')->nullable()->constrained('categories')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            if (Schema::hasColumn('menus', 'category_id')) {
                $table->dropForeignIdFor('categories');
                $table->dropColumn('category_id');
            }
        });
    }
};
