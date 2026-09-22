<?php

namespace App\Http\Requests;

use App\Services\SmartCaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommercialOfferLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inn' => preg_replace('/\D+/', '', (string) $this->input('inn')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'smart_token' => [Rule::requiredIf(app(SmartCaptchaService::class)->enabled('commercial_offer')), 'nullable', 'string', 'max:4096'],
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^(?:\d{10}|\d{12})$/'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:64', 'regex:/^(?=(?:\D*\d){10,15}\D*$)[+\d][\d\s().-]*$/'],
            'privacy_accepted' => ['required', 'accepted'],
            'users' => ['required', 'integer', 'min:1', 'max:500'],
            'billing_period' => ['sometimes', 'string', 'in:month,year'],
            'option_ids' => ['sometimes', 'array', 'max:50'],
            'option_ids.*' => ['integer', 'distinct'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'smart_token.required' => 'Подтвердите, что вы не робот.',
            'name.required' => 'Укажите имя.',
            'inn.required' => 'Укажите ИНН.',
            'inn.regex' => 'ИНН должен содержать 10 или 12 цифр.',
            'company.required' => 'Укажите название компании.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Укажите корректный email.',
            'phone.required' => 'Укажите телефон.',
            'phone.regex' => 'Укажите корректный телефон.',
            'privacy_accepted.accepted' => 'Подтвердите согласие с политикой конфиденциальности.',
            'users.required' => 'Укажите количество пользователей.',
        ];
    }
}
