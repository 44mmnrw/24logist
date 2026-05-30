<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizLeadRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['integer', 'min:1'],
            'recommended_plan_id' => ['nullable', 'integer', 'min:1'],
            'recommended_plan_title' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'phone.required' => 'Укажите телефон.',
            'email.email' => 'Укажите корректный email.',
            'answers.required' => 'Ответьте на вопросы квиза.',
            'answers.min' => 'Ответьте на вопросы квиза.',
        ];
    }
}
