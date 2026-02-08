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
    protected $signature = 'app:backfill-shipway-orders';

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
        // Define your date range here 
        $startDate = Carbon::create(2026, 1, 1); 
        $endDate = Carbon::create(2026, 1, 29);

        $this->info("Dispatching Shipway jobs from {$startDate->toDateString()} to {$endDate->toDateString()} in 1-day chunks...");

        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateString = $current->format('Y-m-d');
            
            // Dispatch job for the specific day
            FetchShipwayOrdersJob::dispatch($dateString);
            
            $this->info("Dispatched Shipway job for date: {$dateString}");

            $current->addDay();
        }

        $this->info("All Shipway jobs dispatched successfully.");
        $this->info("Run 'php artisan queue:work --stop-when-empty' or visit '/job-fire' to process them.");
    }
}
