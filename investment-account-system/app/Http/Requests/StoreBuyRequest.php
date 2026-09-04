<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuyRequest extends FormRequest
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
            'ticker' => ['required', 'string', 'max:20'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price_per_unit' => ['required', 'string', 'decimal:0,4', 'gt:0'],
        ];
    }
}
