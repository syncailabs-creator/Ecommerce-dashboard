<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchMetaAdsDataJob;
use Carbon\Carbon;

class BackfillMetaAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-meta-ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches jobs to backfill Meta Ads data (Campaigns, AdSets, Ads) from Jan 1, 2026 to today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = Carbon::create(2026, 1, 29);
        $endDate = Carbon::now();

        $this->info("Dispatching Meta Ads backfill jobs from {$startDate->toDateString()} to {$endDate->toDateString()}...");

        $current = $startDate->copy();

        while ($current->lt($endDate)) {
            $since = $current->format('Y-m-d');
            // The user requested: {"since":"2026-01-01","until":"2026-01-02"} 
            // This usually implies a 1-day step where 'until' is the next day (exclusive or inclusive depending on API, but following user pattern)
            $until = $current->copy()->addDay()->format('Y-m-d');
            
            // Dispatch jobs for each level
            FetchMetaAdsDataJob::dispatch($since, $until, 'campaign');
            FetchMetaAdsDataJob::dispatch($since, $until, 'adset');
            FetchMetaAdsDataJob::dispatch($since, $until, 'ad');
            
            $this->info("Dispatched jobs for range: since {$since} until {$until}");

            $current->addDay();
        }

        $this->info("All Meta Ads jobs dispatched successfully.");
        $this->info("Run 'php artisan queue:work --stop-when-empty' or visit '/job-fire' to process them.");
    }
}
