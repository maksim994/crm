<?php

namespace App\Http\Requests\Client;

use App\Support\ClientSiteAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClientUser() ?? false;
    }

    public function rules(): array
    {
        $allowedSiteIds = $this->user()
            ? ClientSiteAccess::allowedSiteIds($this->user())
            : [];

        return [
            'site_id' => [
                'nullable',
                'uuid',
                Rule::in($allowedSiteIds),
            ],
            'channel' => ['nullable', 'string', Rule::in(['form', 'call', 'email'])],
            'lead_status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
        ];
    }
}
