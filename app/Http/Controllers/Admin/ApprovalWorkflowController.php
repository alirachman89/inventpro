<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateApprovalWorkflowRequest;
use App\Models\ApprovalWorkflow;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ApprovalWorkflowController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): Response
    {
        $workflows = ApprovalWorkflow::query()
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->orderBy('name')
            ->get()
            ->map(fn (ApprovalWorkflow $workflow) => [
                'id' => $workflow->id,
                'document_type' => $workflow->document_type,
                'name' => $workflow->name,
                'is_active' => $workflow->is_active,
                'steps_count' => $workflow->steps->count(),
                'steps' => $workflow->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'step_order' => $step->step_order,
                    'name' => $step->name,
                    'approver_role' => $step->approver_role,
                    'mode' => $step->mode,
                ]),
            ]);

        return Inertia::render('Admin/Approvals/Workflows/Index', [
            'workflows' => $workflows,
        ]);
    }

    public function edit(ApprovalWorkflow $workflow): Response
    {
        $workflow->load(['steps' => fn ($q) => $q->orderBy('step_order')]);

        return Inertia::render('Admin/Approvals/Workflows/Form', [
            'workflow' => [
                'id' => $workflow->id,
                'document_type' => $workflow->document_type,
                'name' => $workflow->name,
                'is_active' => $workflow->is_active,
                'steps' => $workflow->steps->map(fn ($step) => [
                    'name' => $step->name,
                    'approver_role' => $step->approver_role,
                    'mode' => $step->mode,
                ])->values(),
            ],
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateApprovalWorkflowRequest $request, ApprovalWorkflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $old = [
            'name' => $workflow->name,
            'is_active' => $workflow->is_active,
            'steps' => $workflow->steps()->orderBy('step_order')->get(['name', 'approver_role', 'mode', 'step_order']),
        ];

        $workflow->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'],
        ]);

        $workflow->steps()->delete();

        foreach (array_values($data['steps']) as $index => $step) {
            $workflow->steps()->create([
                'step_order' => $index + 1,
                'name' => $step['name'],
                'approver_role' => $step['approver_role'],
                'mode' => $step['mode'],
            ]);
        }

        $this->auditLogger->log(
            action: 'updated',
            module: 'approval_workflows',
            description: "Workflow {$workflow->document_type} diperbarui",
            auditable: $workflow,
            oldValues: $old,
            newValues: $data,
        );

        return redirect()
            ->route('admin.approval-workflows.index')
            ->with('success', 'Workflow approval berhasil disimpan.');
    }
}
