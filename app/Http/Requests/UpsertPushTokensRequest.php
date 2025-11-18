<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPushTokensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['nullable','string','min:10','max:4096'],
            'device_info' => ['nullable','string','max:255'],
            'tokens' => ['nullable','array'],
            'tokens.*.fcm_token' => ['required_with:tokens','string','min:10','max:4096'],
            'tokens.*.device_info' => ['nullable','string','max:255'],
        ];
    }
}