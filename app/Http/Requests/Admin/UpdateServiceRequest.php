<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceType;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service
            && ($this->user()?->can('update', $service) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $service = $this->route('service');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('services', 'name')->ignore($service?->id)],
            'type' => ['required', Rule::enum(ServiceType::class)],
            'description' => ['required', 'string', 'max:2000'],
            'allow_booking' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('name'))]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'name.unique' => 'Tên dịch vụ đã tồn tại.',
            'type.required' => 'Vui lòng chọn loại dịch vụ.',
            'description.required' => 'Vui lòng nhập mô tả dịch vụ.',
        ];
    }
}
