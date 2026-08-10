<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePreregistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_race_id' => ['required', 'integer', 'exists:event_races,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bib_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
