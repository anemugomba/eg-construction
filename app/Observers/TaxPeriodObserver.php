<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\TaxPeriod;

class TaxPeriodObserver
{
    /**
     * Handle the TaxPeriod "created" event.
     */
    public function created(TaxPeriod $taxPeriod): void
    {
        $vehicle = $taxPeriod->vehicle;
        $vehicleName = $vehicle?->reference_name ?? 'Unknown Vehicle';

        Activity::log(
            Activity::TYPE_TAX_RENEWED,
            "Tax renewed for {$vehicleName}",
            $vehicle?->id,
            $vehicleName
        );

        if ($taxPeriod->penalty_incurred) {
            Activity::log(
                Activity::TYPE_PENALTY_INCURRED,
                "Penalty incurred for {$vehicleName}",
                $vehicle?->id,
                $vehicleName
            );
        }
    }
}
