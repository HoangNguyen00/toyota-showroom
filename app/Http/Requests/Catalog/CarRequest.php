<?php

namespace App\Http\Requests\Catalog;

use App\Enums\CarStatus;
use App\Models\CarCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the admin payload for a single car (one physical car per row).
 * Derived/bookkeeping fields (brand, slug, status default) are never taken from the form.
 */
class CarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route role + CarPolicy authorize the request.
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->input('code')) ? strtoupper(trim($this->input('code'))) : $this->input('code'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'color' => is_string($this->input('color')) ? trim($this->input('color')) : $this->input('color'),
            'is_visible' => $this->boolean('is_visible'),
        ]);
        if (is_array($this->input('specs'))) {
            $this->merge(['specs' => array_values(array_filter($this->input('specs'), fn ($row) => ! is_array($row)
                || filled($row['label'] ?? null) || filled($row['value'] ?? null)))]);
        }
    }

    public function rules(): array
    {
        $car = $this->route('car');

        return [
            'category_id' => [
                'required', 'integer',
                Rule::exists('car_categories', 'id')->where('brand', 'Toyota'),
            ],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9-]*$/', Rule::unique('cars', 'code')->ignore($car?->id)],
            'name' => ['required', 'string', 'max:200'],
            'year' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'color' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1000000', 'max:999999999999999'],
            'status' => ['required', Rule::enum(CarStatus::class)],
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*' => ['array:label,value'],
            'specs.*.label' => ['required', 'string', 'max:100'],
            'specs.*.value' => ['required', 'string', 'max:150'],
            'is_visible' => ['boolean'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=320,min_height=180'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Dòng xe không tồn tại hoặc không thuộc Toyota.',
            'code.regex' => 'Mã xe chỉ gồm chữ in hoa, số và dấu gạch ngang.',
            'code.unique' => 'Mã xe đã được dùng cho một chiếc khác.',
            'price.integer' => 'Giá là số nguyên VND, không dùng dấu chấm hay số lẻ.',
            'images.max' => 'Tối đa 10 ảnh cho một xe.',
            'images.*.image' => 'Tệp tải lên phải là ảnh.',
            'images.*.mimes' => 'Chỉ nhận ảnh JPG, PNG hoặc WEBP.',
            'images.*.max' => 'Mỗi ảnh tối đa 5 MB.',
            'images.*.dimensions' => 'Ảnh phải lớn hơn 320x180 điểm ảnh.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $category = CarCategory::find($this->input('category_id'));
                if ($category && ! $category->is_visible && $this->boolean('is_visible')) {
                    $validator->errors()->add('is_visible', 'Phải bật hiển thị dòng xe trước khi công khai xe.');
                }
            },
        ];
    }
}
