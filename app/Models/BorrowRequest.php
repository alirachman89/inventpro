<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BorrowRequest extends Model
{
    use HasUuid;

    public const DOCUMENT_TYPE = 'borrow_request';

    public const STATUSES = [
        'draft',
        'submitted',
        'approved',
        'rejected',
        'checked_out',
        'partially_returned',
        'returned',
        'cancelled',
    ];

    protected $fillable = [
        'number',
        'status',
        'borrower_user_id',
        'client_id',
        'borrow_date',
        'due_date',
        'purpose',
        'notes',
        'created_by',
        'submitted_at',
        'approved_at',
        'checked_out_at',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BorrowRequestLine::class);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function canSubmit(): bool
    {
        return $this->canEdit() && $this->lines()->exists();
    }

    public function canCheckout(): bool
    {
        return $this->status === 'approved';
    }

    public function canReturn(): bool
    {
        return in_array($this->status, ['checked_out', 'partially_returned'], true);
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        if (! in_array($this->status, ['checked_out', 'partially_returned'], true)) {
            return false;
        }

        return $this->due_date->lt(Carbon::today());
    }
}
