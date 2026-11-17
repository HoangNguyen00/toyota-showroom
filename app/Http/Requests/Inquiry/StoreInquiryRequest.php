<?php

namespace App\Http\Requests\Inquiry;

use App\Models\Inquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Inquiry::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(0[3-9])\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'car_id' => ['nullable', 'integer', 'exists:cars,id'],
        ];
    }
}
