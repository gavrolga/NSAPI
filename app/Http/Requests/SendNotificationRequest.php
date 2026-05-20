<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel'         => ['required', 'string', 'in:email,sms'],
            'message'         => ['required', 'string', 'max:1000'],
            'priority'        => ['sometimes', 'string', 'in:high,low'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'recipient_ids'   => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['required', 'integer', 'exists:subscribers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.in'           => 'Channel must be email or sms.',
            'priority.in'          => 'Priority must be high or low.',
            'recipient_ids.exists' => 'One or more subscribers not found.',
        ];
    }
}
