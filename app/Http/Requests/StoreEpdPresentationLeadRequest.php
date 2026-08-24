<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEpdPresentationLeadRequest extends FormRequest
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
            'company' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^(?:\d{10}|\d{12})$/'],
            'role' => ['required', Rule::in(['expeditor', 'carrier', 'shipper'])],
            'document_system' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company.required' => 'Укажите компанию.',
            'inn.required' => 'Укажите ИНН.',
            'inn.regex' => 'ИНН должен содержать 10 или 12 цифр.',
            'role.required' => 'Выберите роль компании.',
            'role.in' => 'Выберите роль компании из списка.',
            'document_system.required' => 'Укажите, в какой системе вы формируете документы.',
            'contact.required' => 'Укажите контактное лицо.',
            'phone.required' => 'Укажите телефон для связи.',
        ];
    }
}
