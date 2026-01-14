<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasUuids;
    protected $fillable = [
        'type',
        'message',
        'vehicle_id',
        'vehicle_name',
    ];

    public const TYPE_TAX_RENEWED = 'tax_renewed';
    public const TYPE_TAX_EXPIRED = 'tax_expired';
    public const TYPE_VEHICLE_ADDED = 'vehicle_added';
    public const TYPE_VEHICLE_UPDATED = 'vehicle_updated';
    public const TYPE_VEHICLE_DELETED = 'vehicle_deleted';
    public const TYPE_PENALTY_INCURRED = 'penalty_incurred';
    public const TYPE_EXEMPTION_STARTED = 'exemption_started';
    public const TYPE_EXEMPTION_ENDED = 'exemption_ended';
    public const TYPE_EXEMPTION_CANCELLED = 'exemption_cancelled';

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public static function log(string $type, string $message, ?string $vehicleId = null, ?string $vehicleName = null): self
    {
        return self::create([
            'type' => $type,
            'message' => $message,
            'vehicle_id' => $vehicleId,
            'vehicle_name' => $vehicleName,
        ]);
    }
}
