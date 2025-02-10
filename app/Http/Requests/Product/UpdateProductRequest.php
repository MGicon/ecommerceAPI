<?php

namespace App\Http\Requests\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => 'string|max:255',
            'description' => 'string|max:255',
            'quantity' => 'integer|min:1',
            'live' => 'boolean',
            'price' => 'min:1|max:99998.99|numeric|decimal:0,2',
            'priceBefore' => '|max:99999.99|numeric|decimal:0,2|gt:price',
            'image' => 'image|mimes:jpeg,jpg,png|max:2048',
            'additional_images' => 'array|max:20',
            'additional_images.*' => 'image|mimes:jpeg,jpg,png|max:2048',
            'category_id' => 'integer|min:1|exists:categories,id',
            'special_offer' => 'date_format:Y-m-d h:i:s A|after_or_equal:' . date(DATE_ATOM),
            'daily_offer' => 'date_format:Y-m-d h:i:s A|after_or_equal:' . date(DATE_ATOM),
        ];
    }
}
