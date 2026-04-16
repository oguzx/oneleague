<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seed' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
