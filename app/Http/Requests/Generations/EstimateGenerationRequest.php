<?php

namespace App\Http\Requests\Generations;

use App\Http\Requests\Generations\Concerns\HasGenerationInputRules;
use App\Services\Pricing\DTOs\EstimationInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EstimateGenerationRequest extends FormRequest
{
    use HasGenerationInputRules;

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
        return $this->baseGenerationRules();
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->baseGenerationMessages(), [
            'prompt.required' => 'A prompt is required to calculate pricing.',
        ]);
    }

    /**
     * Convert validated request into EstimationInput DTO.
     */
    public function toEstimationInput(): EstimationInput
    {
        return EstimationInput::fromArray($this->validated());
    }
}
