<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class IndexOrderRequest extends FormRequest
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
            'query' => 'string',
            'status' => 'string|in:Pending,Confirmed,Completed,Canceled,Rejected',
            'sort_by' => 'string|in:id,name,city', // also take asc or desc
            'asc' => 'boolean|required_with:sort_by',
            'per_page' => 'integer|min:1|max:30',
            'from' => 'date_format:Y-m-d h:i:s A',
            'to' => 'date_format:Y-m-d h:i:s A|after:from',
        ];
    }
}
