<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchShopifyOrdersJob;
use Carbon\Carbon;

class BackfillShopifyOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-shopify-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches jobs to backfill Shopify orders from Jan 1, 2026 to Feb 4, 2026 in 1-hour chunks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = Carbon::create(2026, 1, 1, 0, 0, 0); // Jan 1, 2026
        $endDate = Carbon::create(2026, 2, 4, 23, 59, 59); // Feb 4, 2026 (End of day)

        $this->info("Dispatching jobs from $startDate to $endDate in 1-hour chunks...");

        $current = $startDate->copy();

        while ($current->lt($endDate)) {
            $chunkStart = $current->copy();
            $chunkEnd = $current->copy()->addHour();

            if ($chunkEnd->gt($endDate)) {
                $chunkEnd = $endDate->copy();
            }

            // Dispatch job
            FetchShopifyOrdersJob::dispatch($chunkStart->toIso8601String(), $chunkEnd->toIso8601String());
            
            $this->info("Dispatched job for range: " . $chunkStart->toDateTimeString() . " -> " . $chunkEnd->toDateTimeString());

            $current->addHour();
        }

        $this->info("All jobs dispatched successfully.");
        $this->info("Run 'php artisan queue:work --stop-when-empty' or visit '/job-fire' to process them.");
    }
}
