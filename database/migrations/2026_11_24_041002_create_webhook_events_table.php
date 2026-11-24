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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('provider_generation_id', 64)->nullable()->index();
            $table->string('event_type', 50)->index();
            $table->json('payload');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('delivery_attempt')->default(1);
            $table->text('processing_error')->nullable();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
