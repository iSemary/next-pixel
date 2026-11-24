<?php

namespace App\Services\Pricing\DTOs;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Services\Pricing\Exceptions\InvalidEstimationInputException;
use ValueError;

readonly class EstimationInput
{
    /**
     * @param  array<string>  $referenceImages
     */
    public function __construct(
        public string $prompt,
        public ImageSize $size = ImageSize::Square,
        public ImageQuality $quality = ImageQuality::Medium,
        public array $referenceImages = [],
    ) {
        $length = mb_strlen($this->prompt, 'UTF-8');
        if ($length > 4000) {
            throw new InvalidEstimationInputException("Prompt must not exceed 4,000 characters (given {$length}).");
        }

        if (count($this->referenceImages) > 4) {
            throw new InvalidEstimationInputException('At most 4 reference images are supported (given '.count($this->referenceImages).').');
        }
    }

    /**
     * Create an EstimationInput DTO from an array of inputs.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawPrompt = (string) ($data['prompt'] ?? '');

        $rawSize = $data['size'] ?? ImageSize::Square;
        if (is_string($rawSize)) {
            try {
                $rawSize = ImageSize::from($rawSize);
            } catch (ValueError) {
                throw new InvalidEstimationInputException("Invalid image size: [{$rawSize}]. Supported sizes: 1024x1024, 1536x1024, 1024x1536.");
            }
        } elseif (! ($rawSize instanceof ImageSize)) {
            throw new InvalidEstimationInputException('Invalid image size provided.');
        }

        $rawQuality = $data['quality'] ?? ImageQuality::Medium;
        if (is_string($rawQuality)) {
            try {
                $rawQuality = ImageQuality::from(strtolower($rawQuality));
            } catch (ValueError) {
                throw new InvalidEstimationInputException("Invalid image quality: [{$rawQuality}]. Supported qualities: low, medium, high.");
            }
        } elseif (! ($rawQuality instanceof ImageQuality)) {
            throw new InvalidEstimationInputException('Invalid image quality provided.');
        }

        $referenceImages = $data['reference_images'] ?? $data['referenceImages'] ?? [];
        if (! is_array($referenceImages)) {
            throw new InvalidEstimationInputException('Reference images must be an array of URL strings.');
        }

        return new self(
            prompt: $rawPrompt,
            size: $rawSize,
            quality: $rawQuality,
            referenceImages: array_values($referenceImages),
        );
    }
}
