<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get credentials array for authentication.
     *
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'email' => strtolower(trim((string) $this->input('email'))),
            'password' => (string) $this->input('password'),
        ];
    }

    /**
     * Determine if remember me was requested.
     */
    public function remember(): bool
    {
        return $this->boolean('remember');
    }
}
