<?php

namespace App\Console\Commands;

use App\Models\VehicleExemption;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessExpiredExemptions extends Command
{
    protected $signature = 'exemption:process-expired
                            {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Mark expired exemptions as ended';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Processing expired exemptions...');

        if ($isDryRun) {
            $this->warn('[DRY RUN MODE] - No changes will be made');
        }

        $expired = VehicleExemption::where('status', 'active')
            ->where('end_date', '<', Carbon::today())
            ->with('vehicle')
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired exemptions found.');
            return 0;
        }

        $this->info("Found {$expired->count()} expired exemption(s)");

        foreach ($expired as $exemption) {
            $vehicleName = $exemption->vehicle?->reference_name ?? 'Unknown';

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would end exemption #{$exemption->id} for {$vehicleName}");
                continue;
            }

            $exemption->update([
                'status' => 'ended',
                'ended_at' => $exemption->end_date,
            ]);

            $this->info("  Ended exemption #{$exemption->id} for {$vehicleName}");
        }

        $this->newLine();
        $this->info("Processed {$expired->count()} expired exemption(s)");

        return 0;
    }
}
