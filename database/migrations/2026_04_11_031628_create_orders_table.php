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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->text('raw_text');
            $table->text('normalized_text')->nullable();
            $table->enum('source', ['voice', 'text', 'manual'])->default('voice');
            $table->enum('parsing_confidence', ['high', 'low', 'fallback'])->default('high');
            $table->enum('validation_status', ['valid', 'partial', 'invalid'])->default('valid');
            $table->enum('status', ['queued', 'waiting', 'processing', 'done', 'cancelled'])->default('queued')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
