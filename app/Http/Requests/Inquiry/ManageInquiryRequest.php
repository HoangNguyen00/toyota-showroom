<?php

namespace App\Http\Requests\Inquiry;

use App\Enums\InquiryCloseResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('*.assign') ? 'assign' : 'manage', $this->route('inquiry'));
    }

    public function rules(): array
    {
        return match (true) {
            $this->routeIs('*.assign') => ['assigned_to' => ['required', 'integer', 'exists:users,id']],
            $this->routeIs('*.note') => ['internal_note' => ['nullable', 'string', 'max:10000']],
            $this->routeIs('*.close') => ['close_result' => ['required', Rule::enum(InquiryCloseResult::class)]],
            default => [],
        };
    }
}
