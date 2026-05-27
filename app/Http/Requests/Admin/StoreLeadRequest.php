<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
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
            'site_id' => ['required', 'uuid', 'exists:sites,id'],
            'phone' => ['nullable', 'string', 'max:32', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'form_description' => ['nullable', 'string', 'max:5000'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'product_request' => ['nullable', 'string', 'max:255'],
            'metrika_client_id' => ['nullable', 'string', 'max:64'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_campaign_first' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required_without' => 'Укажите телефон или email.',
            'email.required_without' => 'Укажите телефон или email.',
        ];
    }
}
