<?php

namespace App\Http\Resources;

use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Generation
 */
class GenerationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_generation_id' => $this->provider_generation_id,
            'idempotency_key' => $this->idempotency_key,
            'prompt' => $this->prompt,
            'size' => $this->size->value,
            'quality' => $this->quality->value,
            'reference_images' => $this->reference_images ?? [],
            'metadata' => $this->metadata ?? (object) [],
            'status' => $this->status->value,
            'provider_status' => $this->provider_status,
            'image_url' => $this->image_url,
            'image_url_expires_at' => $this->image_url_expires_at?->toIso8601String(),
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
            'is_settled' => $this->isSettled(),
            'estimated_tokens' => $this->estimated_total_tokens_max,
            'estimated_input_text_tokens' => $this->estimated_input_text_tokens,
            'estimated_input_image_tokens' => $this->estimated_input_image_tokens,
            'estimated_output_tokens' => $this->estimated_output_tokens,
            'estimated_reasoning_tokens_min' => $this->estimated_reasoning_tokens_min,
            'estimated_reasoning_tokens_max' => $this->estimated_reasoning_tokens_max,
            'estimated_total_tokens_min' => $this->estimated_total_tokens_min,
            'estimated_total_tokens_max' => $this->estimated_total_tokens_max,
            'estimated_provider_cost' => $this->estimated_provider_cost,
            'estimated_user_cost' => $this->estimated_user_cost,
            'actual_tokens' => $this->actual_total_tokens,
            'actual_input_text_tokens' => $this->actual_input_text_tokens,
            'actual_input_image_tokens' => $this->actual_input_image_tokens,
            'actual_output_tokens' => $this->actual_output_tokens,
            'actual_reasoning_tokens' => $this->actual_reasoning_tokens,
            'actual_total_tokens' => $this->actual_total_tokens,
            'actual_provider_cost' => $this->actual_provider_cost,
            'actual_user_cost' => $this->actual_user_cost,
            'applied_markup_rate' => $this->applied_markup_rate,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
