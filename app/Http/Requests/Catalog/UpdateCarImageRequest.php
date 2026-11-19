<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadImage', $this->route('car'));
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
