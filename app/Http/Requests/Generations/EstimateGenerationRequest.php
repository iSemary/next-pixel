<?php

namespace App\Http\Requests\Generations;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Services\Pricing\DTOs\EstimationInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateGenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
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
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'A prompt is required to calculate pricing.',
            'prompt.max' => 'The prompt cannot exceed 4,000 characters.',
            'size.enum' => 'The selected image size is not supported.',
            'quality.enum' => 'The selected quality setting is not supported.',
            'reference_images.max' => 'At most 4 reference images are supported.',
            'reference_images.*.url' => 'Each reference image must be a valid URL.',
        ];
    }

    /**
     * Convert validated request into EstimationInput DTO.
     */
    public function toEstimationInput(): EstimationInput
    {
        return EstimationInput::fromArray($this->validated());
    }
}
