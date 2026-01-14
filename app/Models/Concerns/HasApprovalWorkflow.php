<?php

namespace App\Models\Concerns;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

trait HasApprovalWorkflow
{
    /**
     * The user who submitted this entry.
     */
    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * The user who approved this entry.
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The previous submission (if this is a resubmission).
     */
    public function previousSubmission(): BelongsTo
    {
        return $this->belongsTo(static::class, 'previous_submission_id');
    }

    /**
     * Check if entry is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if entry is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if entry is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if entry is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if entry is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Check if entry can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if entry can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if entry can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Submit entry for approval.
     */
    public function submit(string $userId): self
    {
        $this->update([
            'status' => 'pending',
            'submitted_by' => $userId,
            'submitted_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Approve entry.
     */
    public function approve(string $userId): self
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Reject entry.
     */
    public function reject(string $userId, string $reason): self
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Archive entry.
     */
    public function archive(): self
    {
        $this->update([
            'status' => 'archived',
        ]);

        return $this;
    }

    /**
     * Scope for draft entries.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for pending entries.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved entries.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected entries.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for entries requiring approval.
     */
    public function scopeRequiringApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for finalized (approved or rejected) entries.
     */
    public function scopeFinalized(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }
}
