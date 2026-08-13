<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approvals.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.approver_role' => ['required', 'string', 'exists:roles,name'],
            'steps.*.mode' => ['required', Rule::in(['any', 'all'])],
        ];
    }
}
