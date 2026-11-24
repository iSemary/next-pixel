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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();

            // Ownership & Identity
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('provider_generation_id', 64)->nullable()->unique();
            $table->string('idempotency_key', 255)->unique();

            // Configuration
            $table->text('prompt');
            $table->string('size', 20)->default('1024x1024');
            $table->string('quality', 20)->default('medium');
            $table->json('reference_images')->nullable();
            $table->json('metadata')->nullable();

            // Lifecycle & Provider State
            $table->string('status', 30)->default('queued')->index();
            $table->string('provider_status', 30)->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            // Execution Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();

            // Estimated Token Usage
            $table->unsignedInteger('estimated_input_text_tokens')->nullable();
            $table->unsignedInteger('estimated_input_image_tokens')->nullable();
            $table->unsignedInteger('estimated_output_tokens')->nullable();
            $table->unsignedInteger('estimated_reasoning_tokens_min')->nullable();
            $table->unsignedInteger('estimated_reasoning_tokens_max')->nullable();
            $table->unsignedInteger('estimated_total_tokens_min')->nullable();
            $table->unsignedInteger('estimated_total_tokens_max')->nullable();

            // Actual Token Usage (Known on completion)
            $table->unsignedInteger('actual_input_text_tokens')->nullable();
            $table->unsignedInteger('actual_input_image_tokens')->nullable();
            $table->unsignedInteger('actual_output_tokens')->nullable();
            $table->unsignedInteger('actual_reasoning_tokens')->nullable();
            $table->unsignedInteger('actual_total_tokens')->nullable();

            // Financials (Exact 9-decimal precision for nanodollar accuracy)
            $table->decimal('estimated_provider_cost', 18, 9)->nullable();
            $table->decimal('estimated_user_cost', 18, 9)->nullable();
            $table->decimal('actual_provider_cost', 18, 9)->nullable();
            $table->decimal('actual_user_cost', 18, 9)->nullable();
            $table->decimal('applied_markup_rate', 6, 4)->default(0.2500);
            $table->json('pricing_rate_snapshot')->nullable();
            $table->timestamp('settled_at')->nullable()->index();

            $table->timestamps();

            // Composite indexes for efficient querying and financial reporting
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['completed_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
