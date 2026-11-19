<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'slug' => is_string($name) ? Str::slug($name) : null,
            'is_visible' => $this->boolean('is_visible'),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('car_categories', 'name')->ignore($category?->id),
            ],
            'slug' => [
                'required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('car_categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Tên dòng xe phải có chữ để tạo đường dẫn.',
            'slug.unique' => 'Đường dẫn này đã dùng cho dòng xe khác.',
            'name.unique' => 'Tên dòng xe đã tồn tại.',
        ];
    }

    /**
     * Brand is fixed by the server; never fillable from the form.
     */
    public function categoryAttributes(): array
    {
        return [
            'brand' => 'Toyota',
            ...$this->safe()->only(['name', 'slug', 'description']),
            'is_visible' => $this->boolean('is_visible'),
        ];
    }
}
