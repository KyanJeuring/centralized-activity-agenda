<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'id' => 'prohibited',
            'app_id' => 'prohibited',
            'title' => 'required|string',
            'organizer' => 'required|string',
            'description' => 'required|string',
            'url' => 'required|string',
            'start_date' => 'nullable|date',
            'location' => 'nullable|string',
            'img' => 'nullable|string',
        ];
    }
}
