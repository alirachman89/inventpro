<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalDemo;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\PurchaseOrder;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ApprovalEngine
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function submit(
        string $documentType,
        Model $document,
        User $submitter,
        string $documentLabel,
    ): ?ApprovalRequest {
        $workflow = ApprovalWorkflow::query()
            ->with('steps')
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if (! $workflow || $workflow->steps->isEmpty()) {
            $this->markDocumentApproved($documentType, $document);

            return null;
        }

        return DB::transaction(function () use ($workflow, $documentType, $document, $submitter, $documentLabel) {
            $firstStep = $workflow->steps->sortBy('step_order')->first();

            $request = ApprovalRequest::query()->create([
                'workflow_id' => $workflow->id,
                'document_type' => $documentType,
                'document_id' => $document->getKey(),
                'document_label' => $documentLabel,
                'submitted_by' => $submitter->id,
                'current_step_order' => $firstStep->step_order,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            $this->markDocumentPending($documentType, $document);

            $this->notifyApproversForStep($request, $firstStep);

            $this->auditLogger->log(
                action: 'submitted',
                module: 'approvals',
                description: "Dokumen diajukan approval: {$documentLabel}",
                auditable: $request,
                newValues: [
                    'document_type' => $documentType,
                    'document_id' => $document->getKey(),
                ],
                actor: $submitter,
            );

            return $request;
        });
    }

    public function approve(ApprovalRequest $request, User $actor, ?string $comment = null): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $actor, $comment) {
            $request->load(['workflow.steps', 'actions']);

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'approval' => 'Permintaan approval sudah tidak aktif.',
                ]);
            }

            $step = $request->currentStep();

            if (! $step || ! $this->userCanActOnStep($actor, $step)) {
                throw ValidationException::withMessages([
                    'approval' => 'Anda tidak berwenang menyetujui langkah ini.',
                ]);
            }

            if ($this->hasActedOnStep($request, $actor, $step->step_order)) {
                throw ValidationException::withMessages([
                    'approval' => 'Anda sudah memberikan keputusan pada langkah ini.',
                ]);
            }

            ApprovalAction::query()->create([
                'approval_request_id' => $request->id,
                'step_id' => $step->id,
                'step_order' => $step->step_order,
                'actor_user_id' => $actor->id,
                'action' => 'approve',
                'comment' => $comment,
                'created_at' => now(),
            ]);

            if ($this->isStepCompleted($request->fresh(['actions']), $step)) {
                $nextStep = $request->workflow->steps
                    ->where('step_order', '>', $step->step_order)
                    ->sortBy('step_order')
                    ->first();

                if ($nextStep) {
                    $request->update(['current_step_order' => $nextStep->step_order]);
                    $this->notifyApproversForStep($request->fresh(), $nextStep);
                } else {
                    $request->update([
                        'status' => 'approved',
                        'current_step_order' => null,
                        'completed_at' => now(),
                    ]);

                    $this->markDocumentApproved($request->document_type, $this->resolveDocument($request));

                    $this->notifications->sendToUsers(
                        [$request->submitted_by],
                        'approval_result',
                        'Dokumen disetujui',
                        "Permintaan \"{$request->document_label}\" telah disetujui sepenuhnya.",
                        route('approvals.show', $request->id),
                    );
                }
            }

            $this->auditLogger->log(
                action: 'approved',
                module: 'approvals',
                description: "Menyetujui: {$request->document_label}",
                auditable: $request,
                newValues: ['comment' => $comment, 'step' => $step->step_order],
                actor: $actor,
            );

            return $request->fresh(['actions.actor', 'workflow.steps', 'submitter']);
        });
    }

    public function reject(ApprovalRequest $request, User $actor, string $comment): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $actor, $comment) {
            $request->load(['workflow.steps', 'actions']);

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'approval' => 'Permintaan approval sudah tidak aktif.',
                ]);
            }

            $step = $request->currentStep();

            if (! $step || ! $this->userCanActOnStep($actor, $step)) {
                throw ValidationException::withMessages([
                    'approval' => 'Anda tidak berwenang menolak langkah ini.',
                ]);
            }

            ApprovalAction::query()->create([
                'approval_request_id' => $request->id,
                'step_id' => $step->id,
                'step_order' => $step->step_order,
                'actor_user_id' => $actor->id,
                'action' => 'reject',
                'comment' => $comment,
                'created_at' => now(),
            ]);

            $request->update([
                'status' => 'rejected',
                'current_step_order' => null,
                'completed_at' => now(),
            ]);

            $this->markDocumentRejected($request->document_type, $this->resolveDocument($request));

            $this->notifications->sendToUsers(
                [$request->submitted_by],
                'approval_result',
                'Dokumen ditolak',
                "Permintaan \"{$request->document_label}\" ditolak. Catatan: {$comment}",
                route('approvals.show', $request->id),
            );

            $this->auditLogger->log(
                action: 'rejected',
                module: 'approvals',
                description: "Menolak: {$request->document_label}",
                auditable: $request,
                newValues: ['comment' => $comment, 'step' => $step->step_order],
                actor: $actor,
            );

            return $request->fresh(['actions.actor', 'workflow.steps', 'submitter']);
        });
    }

    public function userCanActOnStep(User $user, ApprovalStep $step): bool
    {
        return $user->isSuperAdmin() || $user->hasRole($step->approver_role);
    }

    public function userCanActOnRequest(User $user, ApprovalRequest $request): bool
    {
        if ($request->status !== 'pending') {
            return false;
        }

        $step = $request->currentStep();

        if (! $step) {
            return false;
        }

        if (! $this->userCanActOnStep($user, $step)) {
            return false;
        }

        return ! $this->hasActedOnStep($request, $user, $step->step_order);
    }

    private function hasActedOnStep(ApprovalRequest $request, User $user, int $stepOrder): bool
    {
        return $request->actions
            ->where('step_order', $stepOrder)
            ->where('actor_user_id', $user->id)
            ->isNotEmpty();
    }

    private function isStepCompleted(ApprovalRequest $request, ApprovalStep $step): bool
    {
        $approvals = $request->actions
            ->where('step_order', $step->step_order)
            ->where('action', 'approve');

        if ($step->mode === 'all') {
            $required = User::role($step->approver_role)->count();

            return $required > 0 && $approvals->count() >= $required;
        }

        return $approvals->isNotEmpty();
    }

    private function notifyApproversForStep(ApprovalRequest $request, ApprovalStep $step): void
    {
        $users = User::role($step->approver_role)->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->notifications->sendToUsers(
            $users,
            'approval_request',
            'Menunggu persetujuan Anda',
            "Dokumen \"{$request->document_label}\" menunggu approval pada langkah: {$step->name}.",
            route('approvals.show', $request->id),
            [
                'approval_request_id' => $request->id,
                'step_order' => $step->step_order,
            ],
        );
    }

    private function resolveDocument(ApprovalRequest $request): ?Model
    {
        return match ($request->document_type) {
            ApprovalDemo::DOCUMENT_TYPE => ApprovalDemo::query()->find($request->document_id),
            PurchaseOrder::DOCUMENT_TYPE => PurchaseOrder::query()->find($request->document_id),
            StockOpname::DOCUMENT_TYPE => StockOpname::query()->find($request->document_id),
            default => null,
        };
    }

    private function markDocumentPending(string $documentType, Model $document): void
    {
        if ($documentType === ApprovalDemo::DOCUMENT_TYPE && $document instanceof ApprovalDemo) {
            $document->update(['status' => 'pending_approval']);
        }

        if ($documentType === PurchaseOrder::DOCUMENT_TYPE && $document instanceof PurchaseOrder) {
            $document->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        }

        if ($documentType === StockOpname::DOCUMENT_TYPE && $document instanceof StockOpname) {
            $document->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        }
    }

    private function markDocumentApproved(string $documentType, ?Model $document): void
    {
        if ($documentType === ApprovalDemo::DOCUMENT_TYPE && $document instanceof ApprovalDemo) {
            $document->update(['status' => 'approved']);
        }

        if ($documentType === PurchaseOrder::DOCUMENT_TYPE && $document instanceof PurchaseOrder) {
            // Langsung ordered agar warehouse dapat membuat GR
            $document->update([
                'status' => 'ordered',
                'approved_at' => now(),
            ]);
        }

        if ($documentType === StockOpname::DOCUMENT_TYPE && $document instanceof StockOpname) {
            $document->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        }
    }

    private function markDocumentRejected(string $documentType, ?Model $document): void
    {
        if ($documentType === ApprovalDemo::DOCUMENT_TYPE && $document instanceof ApprovalDemo) {
            $document->update(['status' => 'rejected']);
        }

        if ($documentType === PurchaseOrder::DOCUMENT_TYPE && $document instanceof PurchaseOrder) {
            $document->update(['status' => 'rejected']);
        }

        if ($documentType === StockOpname::DOCUMENT_TYPE && $document instanceof StockOpname) {
            $document->update(['status' => 'rejected']);
        }
    }
}
