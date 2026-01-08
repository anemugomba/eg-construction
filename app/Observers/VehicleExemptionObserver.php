<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\VehicleExemption;

class VehicleExemptionObserver
{
    /**
     * Handle the VehicleExemption "created" event.
     */
    public function created(VehicleExemption $exemption): void
    {
        $vehicle = $exemption->vehicle;
        $vehicleName = $vehicle?->reference_name ?? 'Unknown Vehicle';

        Activity::log(
            Activity::TYPE_EXEMPTION_STARTED,
            "Exemption started for {$vehicleName} ({$exemption->duration_months} months)",
            $vehicle?->id,
            $vehicleName
        );
    }

    /**
     * Handle the VehicleExemption "updated" event.
     */
    public function updated(VehicleExemption $exemption): void
    {
        if ($exemption->isDirty('status')) {
            $vehicle = $exemption->vehicle;
            $vehicleName = $vehicle?->reference_name ?? 'Unknown Vehicle';

            if ($exemption->status === 'ended') {
                Activity::log(
                    Activity::TYPE_EXEMPTION_ENDED,
                    "Exemption ended for {$vehicleName}",
                    $vehicle?->id,
                    $vehicleName
                );
            } elseif ($exemption->status === 'cancelled') {
                Activity::log(
                    Activity::TYPE_EXEMPTION_CANCELLED,
                    "Exemption cancelled for {$vehicleName}",
                    $vehicle?->id,
                    $vehicleName
                );
            }
        }
    }
}
