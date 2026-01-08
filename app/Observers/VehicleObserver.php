<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Vehicle;

class VehicleObserver
{
    /**
     * Handle the Vehicle "created" event.
     */
    public function created(Vehicle $vehicle): void
    {
        Activity::log(
            Activity::TYPE_VEHICLE_ADDED,
            "Vehicle added: {$vehicle->reference_name}",
            $vehicle->id,
            $vehicle->reference_name
        );
    }

    /**
     * Handle the Vehicle "updated" event.
     */
    public function updated(Vehicle $vehicle): void
    {
        Activity::log(
            Activity::TYPE_VEHICLE_UPDATED,
            "Vehicle updated: {$vehicle->reference_name}",
            $vehicle->id,
            $vehicle->reference_name
        );
    }

    /**
     * Handle the Vehicle "deleted" event.
     */
    public function deleted(Vehicle $vehicle): void
    {
        Activity::log(
            Activity::TYPE_VEHICLE_DELETED,
            "Vehicle deleted: {$vehicle->reference_name}",
            null,
            $vehicle->reference_name
        );
    }
}
