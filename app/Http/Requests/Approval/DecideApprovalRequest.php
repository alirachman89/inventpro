<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class DecideApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approvals.act') ?? false;
    }

    public function rules(): array
    {
        $isReject = $this->routeIs('approvals.reject');

        return [
            'comment' => [$isReject ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Komentar wajib diisi saat menolak dokumen.',
        ];
    }
}
