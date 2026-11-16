<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled per-action via Policy + Service.
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy lịch.',
            'cancel_reason.max' => 'Lý do hủy tối đa 500 ký tự.',
        ];
    }
}
