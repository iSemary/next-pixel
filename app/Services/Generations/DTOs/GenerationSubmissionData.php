<?php

namespace App\Services\Generations\DTOs;

use App\Services\Pricing\DTOs\EstimationInput;

readonly class GenerationSubmissionData
{
    /**
     * @param  array<string, string>  $metadata
     */
    public function __construct(
        public EstimationInput $input,
        public array $metadata = [],
        public int $numGenerations = 1,
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create an instance from array data by reusing EstimationInput normalization.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $normalized = $data;
        $normalized['prompt'] = trim((string) ($data['prompt'] ?? ''));

        $input = EstimationInput::fromArray($normalized);

        $rawMetadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $metadata = [];
        foreach ($rawMetadata as $key => $value) {
            if ($value !== null) {
                $metadata[(string) $key] = (string) $value;
            }
        }

        $numGenerations = max(1, min(4, (int) ($data['num_generations'] ?? $data['numGenerations'] ?? 1)));
        $rawKey = $data['idempotency_key'] ?? $data['idempotencyKey'] ?? null;
        $idempotencyKey = is_string($rawKey) && trim($rawKey) !== '' ? trim($rawKey) : null;

        return new self(
            input: $input,
            metadata: $metadata,
            numGenerations: $numGenerations,
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * Convert to EstimationInput DTO for pricing calculation.
     */
    public function toEstimationInput(): EstimationInput
    {
        return $this->input;
    }
}
