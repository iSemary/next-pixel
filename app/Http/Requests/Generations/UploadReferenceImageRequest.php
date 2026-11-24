<?php

namespace App\Http\Requests\Generations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadReferenceImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please choose an image file to upload.',
            'image.image' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Supported image formats: PNG, JPG, JPEG, or WEBP.',
            'image.max' => 'Reference image size must not exceed 5 MB.',
        ];
    }
}
