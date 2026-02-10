<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchShipwayOrdersJob;
use Carbon\Carbon;

class BackfillShipwayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-shipway-orders {--sync : Run jobs synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches jobs to backfill Shipway orders in 1-day chunks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        // Define your date range here 
        $startDate = Carbon::create(2026, 2, 10); 
        $endDate = Carbon::create(2026, 2, 10);

        $sync = $this->option('sync');
        
        $this->info("Starting Shipway backfill from {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->info("Mode: " . ($sync ? "Synchronous (immediate execution)" : "Queued (background processing)"));
        $this->newLine();

        $current = $startDate->copy();
        $totalDays = 0;

        while ($current->lte($endDate)) {
            $dateString = $current->format('Y-m-d');
            $totalDays++;
            
            if ($sync) {
                // Run synchronously for immediate execution
                $this->info("Processing date: {$dateString}...");
                $job = new FetchShipwayOrdersJob($dateString);
                $job->handle();
                $this->info("✓ Completed: {$dateString}");
            } else {
                // Dispatch to queue
                FetchShipwayOrdersJob::dispatch($dateString);
                $this->info("✓ Queued: {$dateString}");
            }

            $current->addDay();
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("- Total days processed: {$totalDays}");
        $this->info("- Execution time: {$executionTime} seconds");
        
        if (!$sync) {
            $this->newLine();
            $this->info("Jobs have been queued. Process them with:");
            $this->info("  php artisan queue:work --stop-when-empty");
            $this->info("  OR visit /job-fire");
        }
    }
}
