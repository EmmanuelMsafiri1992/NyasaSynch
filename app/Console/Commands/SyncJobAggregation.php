<?php

namespace App\Console\Commands;

use App\Services\JobAggregationService;
use App\Models\JobAggregationSource;
use Illuminate\Console\Command;

class SyncJobAggregation extends Command
{
    protected $signature = 'jobs:sync-aggregation
                           {--source= : Specific source slug to sync}
                           {--location= : Filter by location}
                           {--keywords= : Filter by keywords}
                           {--category= : Filter by category}
                           {--force : Force sync even if rate limited}';

    protected $description = 'Sync jobs from external aggregation sources';

    protected $jobAggregationService;

    public function __construct(JobAggregationService $jobAggregationService)
    {
        parent::__construct();
        $this->jobAggregationService = $jobAggregationService;
    }

    public function handle(): int
    {
        $this->info('Starting job aggregation sync...');

        $filters = array_filter([
            'location' => $this->option('location'),
            'keywords' => $this->option('keywords'),
            'category' => $this->option('category'),
        ]);

        try {
            if ($sourceSlug = $this->option('source')) {
                $source = JobAggregationSource::where('slug', $sourceSlug)->first();

                if (!$source) {
                    $this->error("Source '{$sourceSlug}' not found.");
                    return 1;
                }

                if (!$source->canSync() && !$this->option('force')) {
                    $this->warn("Source '{$sourceSlug}' cannot sync due to rate limits or inactive status.");
                    $this->info("Use --force to override rate limiting.");
                    return 1;
                }

                $this->info("Syncing from source: {$source->name}");
                $result = $this->jobAggregationService->syncSource($source, $filters);

                $this->displaySyncResult($source->name, $result);
            } else {
                $this->info("Syncing from all active sources...");
                $results = $this->jobAggregationService->syncAllSources($filters);

                foreach ($results as $sourceName => $result) {
                    $this->displaySyncResult($sourceName, $result);
                }
            }

            $this->info('Job aggregation sync completed successfully.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function displaySyncResult(string $sourceName, array $result): void
    {
        $this->info("Results for {$sourceName}:");

        if (!$result['success']) {
            $this->error("  ❌ Failed: " . ($result['error'] ?? 'Unknown error'));
            return;
        }

        $this->line("  ✅ Success");
        $this->line("  📊 Jobs processed: " . ($result['jobs_processed'] ?? 0));
        $this->line("  ➕ Jobs created: " . ($result['jobs_created'] ?? 0));
        $this->line("  🔄 Jobs updated: " . ($result['jobs_updated'] ?? 0));
        $this->line("  ⏭️  Jobs skipped: " . ($result['jobs_skipped'] ?? 0));

        if (($result['jobs_failed'] ?? 0) > 0) {
            $this->line("  ❌ Jobs failed: " . $result['jobs_failed']);
        }

        $this->line("");
    }
}