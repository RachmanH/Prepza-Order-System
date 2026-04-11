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
        Schema::create('order_queues', function (Blueprint $table) {
            $table->bigIncrements('queue_number');
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['waiting', 'processing', 'done'])->default('waiting')->index();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_queues');
    }
};
