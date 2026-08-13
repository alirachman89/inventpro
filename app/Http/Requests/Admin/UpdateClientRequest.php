<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('clients.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('clients', 'code')->ignore($client->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Client::TYPES)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
