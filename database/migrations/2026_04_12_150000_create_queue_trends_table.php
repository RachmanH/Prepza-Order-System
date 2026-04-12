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
        Schema::create('queue_trends', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('image_url', 2048);
            $table->string('caption', 300)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('source_timestamp')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('source_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_trends');
    }
};
