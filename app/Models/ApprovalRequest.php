<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'workflow_id',
        'document_type',
        'document_id',
        'document_label',
        'submitted_by',
        'current_step_order',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id')->latest('created_at');
    }

    public function currentStep(): ?ApprovalStep
    {
        if (! $this->current_step_order) {
            return null;
        }

        return $this->workflow?->steps
            ->firstWhere('step_order', $this->current_step_order);
    }
}
