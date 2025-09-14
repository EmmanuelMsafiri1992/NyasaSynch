<?php

namespace App\Console\Commands;

use App\Models\AtsConnection;
use App\Services\AtsIntegrationService;
use Illuminate\Console\Command;

class SyncAtsConnections extends Command
{
    protected $signature = 'ats:sync
                           {--connection= : Specific connection ID to sync}
                           {--provider= : Sync connections for specific provider}
                           {--location= : Filter by location}
                           {--keywords= : Filter by keywords}
                           {--department= : Filter by department}
                           {--force : Force sync even if rate limited}';

    protected $description = 'Sync data from ATS connections';

    private AtsIntegrationService $atsService;

    public function __construct(AtsIntegrationService $atsService)
    {
        parent::__construct();
        $this->atsService = $atsService;
    }

    public function handle(): int
    {
        $this->info('Starting ATS synchronization...');

        $filters = array_filter([
            'location' => $this->option('location'),
            'keywords' => $this->option('keywords'),
            'department' => $this->option('department'),
        ]);

        try {
            if ($connectionId = $this->option('connection')) {
                return $this->syncSpecificConnection($connectionId, $filters);
            } elseif ($provider = $this->option('provider')) {
                return $this->syncByProvider($provider, $filters);
            } else {
                return $this->syncAllConnections($filters);
            }
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function syncSpecificConnection(int $connectionId, array $filters): int
    {
        $connection = AtsConnection::find($connectionId);

        if (!$connection) {
            $this->error("ATS connection with ID {$connectionId} not found.");
            return 1;
        }

        if (!$connection->canSync() && !$this->option('force')) {
            $this->warn("Connection '{$connection->name}' cannot sync due to rate limits or inactive status.");
            $this->info("Use --force to override rate limiting.");
            return 1;
        }

        $this->info("Syncing connection: {$connection->name} ({$connection->provider_name})");
        $result = $this->atsService->syncConnection($connection, $filters);

        $this->displaySyncResult($connection->name, $result);

        return $result['success'] ? 0 : 1;
    }

    private function syncByProvider(string $provider, array $filters): int
    {
        $connections = AtsConnection::active()
            ->byProvider($provider)
            ->get();

        if ($connections->isEmpty()) {
            $this->info("No active connections found for provider: {$provider}");
            return 0;
        }

        $this->info("Found {$connections->count()} connections for provider: {$provider}");

        $results = [];
        foreach ($connections as $connection) {
            if ($connection->canSync() || $this->option('force')) {
                $this->info("Syncing: {$connection->name}");
                $results[$connection->name] = $this->atsService->syncConnection($connection, $filters);
            } else {
                $this->warn("Skipping '{$connection->name}' due to rate limits");
                $results[$connection->name] = [
                    'success' => false,
                    'error' => 'Rate limited'
                ];
            }
        }

        foreach ($results as $connectionName => $result) {
            $this->displaySyncResult($connectionName, $result);
        }

        $successful = collect($results)->where('success', true)->count();
        $this->info("Sync completed: {$successful}/{$connections->count()} connections successful");

        return $successful === $connections->count() ? 0 : 1;
    }

    private function syncAllConnections(array $filters): int
    {
        $this->info("Syncing all active ATS connections...");

        $results = $this->atsService->syncAllConnections($filters);

        foreach ($results as $connectionName => $result) {
            $this->displaySyncResult($connectionName, $result);
        }

        $successful = collect($results)->where('success', true)->count();
        $total = count($results);

        $this->info("ATS sync completed: {$successful}/{$total} connections successful");

        return $successful === $total ? 0 : 1;
    }

    private function displaySyncResult(string $connectionName, array $result): void
    {
        $this->line("Results for {$connectionName}:");

        if (!$result['success']) {
            $this->error("  ❌ Failed: " . ($result['error'] ?? 'Unknown error'));
            $this->line("");
            return;
        }

        $this->line("  ✅ Success");

        // Jobs
        if (isset($result['jobs_processed'])) {
            $this->line("  📋 Jobs processed: " . ($result['jobs_processed'] ?? 0));
            $this->line("     ➕ Created: " . ($result['jobs_created'] ?? 0));
            $this->line("     🔄 Updated: " . ($result['jobs_updated'] ?? 0));
        }

        // Candidates
        if (isset($result['candidates_processed'])) {
            $this->line("  👥 Candidates processed: " . ($result['candidates_processed'] ?? 0));
            $this->line("     ➕ Created: " . ($result['candidates_created'] ?? 0));
            $this->line("     🔄 Updated: " . ($result['candidates_updated'] ?? 0));
        }

        // Applications
        if (isset($result['applications_processed'])) {
            $this->line("  📝 Applications processed: " . ($result['applications_processed'] ?? 0));
            $this->line("     ➕ Created: " . ($result['applications_created'] ?? 0));
            $this->line("     🔄 Updated: " . ($result['applications_updated'] ?? 0));
        }

        if (($result['total_failed'] ?? 0) > 0) {
            $this->line("  ❌ Total failed: " . $result['total_failed']);
        }

        $this->line("");
    }
}