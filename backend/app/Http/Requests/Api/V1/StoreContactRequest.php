<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'intent' => ['nullable', 'string', 'in:register,change_mode,delete_account'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('intent')) {
            $this->merge(['intent' => 'register']);
        }
    }
}
