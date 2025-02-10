<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ban' => 'nullable|date_format:Y-m-d h:i:s A|after_or_equal:' . date(DATE_ATOM),
        ];
    }
}
