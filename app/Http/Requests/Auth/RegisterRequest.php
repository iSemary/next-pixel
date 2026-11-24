<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * Get validated user creation data.
     *
     * @return array<string, mixed>
     */
    public function userData(): array
    {
        $email = strtolower(trim((string) $this->input('email')));
        $name = trim((string) $this->input('name'));

        if ($name === '') {
            $name = (string) Str::before($email, '@');
        }

        return [
            'name' => $name,
            'email' => $email,
            'password' => (string) $this->input('password'),
        ];
    }
}
