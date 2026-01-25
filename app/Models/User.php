<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Role constants for RBAC.
     */
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_SENIOR_DPF = 'senior_dpf';
    public const ROLE_SITE_DPF = 'site_dpf';
    public const ROLE_DATA_ENTRY = 'data_entry';
    public const ROLE_VIEW_ONLY = 'view_only';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'notify_email',
        'notify_sms',
        'notify_whatsapp',
        'notify_push',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    // ==========================================
    // Notification Relationships
    // ==========================================

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    public function unreadAppNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Push notification tokens for this user.
     */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    // ==========================================
    // Site Relationships (Many-to-Many)
    // ==========================================

    /**
     * The sites this user is assigned to.
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'user_sites')
            ->withTimestamps();
    }

    /**
     * Check if user has access to a specific site.
     */
    public function hasAccessToSite(string $siteId): bool
    {
        // Administrators have access to all sites
        if ($this->isAdministrator()) {
            return true;
        }

        return $this->sites()->where('sites.id', $siteId)->exists();
    }

    /**
     * Get site IDs this user has access to.
     */
    public function getAccessibleSiteIds(): array
    {
        if ($this->isAdministrator()) {
            return Site::pluck('id')->toArray();
        }

        return $this->sites()->pluck('sites.id')->toArray();
    }

    // ==========================================
    // Fleet Management Relationships
    // ==========================================

    /**
     * Site assignments made by this user.
     */
    public function siteAssignments(): HasMany
    {
        return $this->hasMany(SiteAssignment::class, 'assigned_by');
    }

    /**
     * Readings recorded by this user.
     */
    public function recordings(): HasMany
    {
        return $this->hasMany(Reading::class, 'recorded_by');
    }

    /**
     * Services submitted by this user.
     */
    public function submittedServices(): HasMany
    {
        return $this->hasMany(Service::class, 'submitted_by');
    }

    /**
     * Services approved by this user.
     */
    public function approvedServices(): HasMany
    {
        return $this->hasMany(Service::class, 'approved_by');
    }

    /**
     * Job cards submitted by this user.
     */
    public function submittedJobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'submitted_by');
    }

    /**
     * Job cards approved by this user.
     */
    public function approvedJobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'approved_by');
    }

    /**
     * Inspections submitted by this user.
     */
    public function submittedInspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'submitted_by');
    }

    /**
     * Inspections approved by this user.
     */
    public function approvedInspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'approved_by');
    }

    /**
     * Watch list items created by this user.
     */
    public function createdWatchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class, 'created_by');
    }

    /**
     * Interval overrides made by this user.
     */
    public function intervalOverrides(): HasMany
    {
        return $this->hasMany(IntervalOverride::class, 'changed_by');
    }

    /**
     * Oil analyses created by this user.
     */
    public function oilAnalyses(): HasMany
    {
        return $this->hasMany(OilAnalysis::class, 'created_by');
    }

    /**
     * Attachments uploaded by this user.
     */
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    // ==========================================
    // Role Checking Methods
    // ==========================================

    /**
     * Check if user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    /**
     * Check if user is a Senior DPF.
     */
    public function isSeniorDpf(): bool
    {
        return $this->role === self::ROLE_SENIOR_DPF;
    }

    /**
     * Check if user is a Site DPF.
     */
    public function isSiteDpf(): bool
    {
        return $this->role === self::ROLE_SITE_DPF;
    }

    /**
     * Check if user is a Data Entry user.
     */
    public function isDataEntry(): bool
    {
        return $this->role === self::ROLE_DATA_ENTRY;
    }

    /**
     * Check if user is View Only.
     */
    public function isViewOnly(): bool
    {
        return $this->role === self::ROLE_VIEW_ONLY;
    }

    /**
     * Check if user can approve services/job cards/inspections.
     */
    public function canApprove(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_SENIOR_DPF,
        ]);
    }

    /**
     * Check if user can submit services/job cards/inspections.
     */
    public function canSubmit(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_SENIOR_DPF,
            self::ROLE_SITE_DPF,
        ]);
    }

    /**
     * Check if user can enter data (readings, etc.).
     */
    public function canEnterData(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_SENIOR_DPF,
            self::ROLE_SITE_DPF,
            self::ROLE_DATA_ENTRY,
        ]);
    }

    /**
     * Check if user can override intervals.
     */
    public function canOverrideIntervals(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_SENIOR_DPF,
        ]);
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    /**
     * Check if user can manage sites.
     */
    public function canManageSites(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    /**
     * Check if user can manage machine types.
     */
    public function canManageMachineTypes(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    // ==========================================
    // Role Scopes
    // ==========================================

    /**
     * Scope for administrators only.
     */
    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMINISTRATOR);
    }

    /**
     * Scope for Senior DPF users.
     */
    public function scopeSeniorDpf(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_SENIOR_DPF);
    }

    /**
     * Scope for Site DPF users.
     */
    public function scopeSiteDpf(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_SITE_DPF);
    }

    /**
     * Scope for users who can approve.
     */
    public function scopeCanApprove(Builder $query): Builder
    {
        return $query->whereIn('role', [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_SENIOR_DPF,
        ]);
    }

    /**
     * Scope for users at a specific site.
     */
    public function scopeAtSite(Builder $query, string $siteId): Builder
    {
        return $query->whereHas('sites', function ($q) use ($siteId) {
            $q->where('sites.id', $siteId);
        });
    }

    /**
     * Scope for users with a specific role.
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
}
