<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class CarImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=320,min_height=180'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Chọn một ảnh để tải lên.',
            'image.image' => 'Tệp tải lên phải là ảnh.',
            'image.mimes' => 'Chỉ nhận ảnh JPG, PNG hoặc WEBP.',
            'image.max' => 'Ảnh tối đa 4 MB.',
            'image.dimensions' => 'Ảnh phải lớn hơn 320x180 điểm ảnh.',
        ];
    }
}
