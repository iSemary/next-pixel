<?php

namespace App\Http\Requests\Generations\Concerns;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait HasGenerationInputRules
{
    /**
     * Shared validation rules for generation prompt, dimensions, quality, and reference images.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseGenerationRules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:1', 'max:4000'],
            'size' => ['required', 'string', Rule::enum(ImageSize::class)],
            'quality' => ['required', 'string', Rule::enum(ImageQuality::class)],
            'reference_images' => ['nullable', 'array', 'max:4'],
            'reference_images.*' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * Shared validation error messages for generation inputs.
     *
     * @return array<string, string>
     */
    protected function baseGenerationMessages(): array
    {
        return [
            'prompt.max' => 'The prompt cannot exceed 4,000 characters.',
            'size.enum' => 'The selected image size is not supported.',
            'quality.enum' => 'The selected quality setting is not supported.',
            'reference_images.max' => 'At most 4 reference images are supported.',
            'reference_images.*.url' => 'Each reference image must be a valid URL.',
        ];
    }
}
