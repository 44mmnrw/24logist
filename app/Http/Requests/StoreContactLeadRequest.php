<?php

namespace App\Http\Requests;

use App\Services\SmartCaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'smart_token' => [Rule::requiredIf(app(SmartCaptchaService::class)->enabled('contact')), 'nullable', 'string', 'max:4096'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'smart_token.required' => 'Подтвердите, что вы не робот.',
            'name.required' => 'Укажите имя.',
            'phone.required' => 'Укажите телефон.',
            'email.email' => 'Укажите корректный email.',
            'message.max' => 'Сообщение слишком длинное.',
        ];
    }
}
