<?php

namespace App\Http\Requests\Inquiry;

use App\Policies\MessagePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (new MessagePolicy)->send($this->user(), $this->route('inquiry'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1000'],
            'client_message_id' => ['required', 'uuid'],
        ];
    }
}
