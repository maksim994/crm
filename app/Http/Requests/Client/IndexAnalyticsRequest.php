<?php

namespace App\Http\Requests\Client;

use App\Support\ClientSiteAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $site = $this->route('site');

        return $site !== null
            && ClientSiteAccess::canAccessSite($this->user(), (string) $site->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'group_by' => ['nullable', Rule::in(['day', 'month'])],
            'refresh' => ['nullable', 'boolean'],
        ];
    }
}
