<?php

namespace App\Http\Requests\Generations;

use App\Http\Requests\Generations\Concerns\HasGenerationInputRules;
use App\Services\Generations\DTOs\GenerationSubmissionData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateGenerationRequest extends FormRequest
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
        return array_merge($this->baseGenerationRules(), [
            'num_generations' => ['nullable', 'integer', 'min:1', 'max:4'],
            // Reserve 1 metadata slot for internal nextpixel_generation_id correlation (provider cap: 20)
            'metadata' => ['nullable', 'array', 'max:19'],
            'metadata.*' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->baseGenerationMessages(), [
            'prompt.required' => 'A prompt is required to generate an image.',
            'num_generations.min' => 'At least 1 generation must be requested.',
            'num_generations.max' => 'A maximum of 4 generations can be requested at once.',
            'metadata.max' => 'At most 19 custom metadata entries are supported.',
            'idempotency_key.max' => 'Idempotency key must not exceed 255 characters.',
        ]);
    }

    /**
     * Convert validated request to GenerationSubmissionData DTO.
     */
    public function toSubmissionData(): GenerationSubmissionData
    {
        $data = $this->validated();

        if ($this->hasHeader('Idempotency-Key') && empty($data['idempotency_key'])) {
            $data['idempotency_key'] = $this->header('Idempotency-Key');
        }

        return GenerationSubmissionData::fromArray($data);
    }
}
