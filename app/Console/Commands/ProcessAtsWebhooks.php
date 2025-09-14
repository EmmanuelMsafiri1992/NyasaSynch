<?php

namespace App\Console\Commands;

use App\Models\AtsWebhook;
use App\Services\AtsIntegrationService;
use Illuminate\Console\Command;

class ProcessAtsWebhooks extends Command
{
    protected $signature = 'ats:process-webhooks
                           {--connection= : Specific connection ID to process webhooks for}
                           {--event-type= : Process specific event type}
                           {--failed : Process only failed webhooks}
                           {--retry : Retry failed webhooks}
                           {--limit=100 : Maximum number of webhooks to process}';

    protected $description = 'Process pending ATS webhooks';

    private AtsIntegrationService $atsService;

    public function __construct(AtsIntegrationService $atsService)
    {
        parent::__construct();
        $this->atsService = $atsService;
    }

    public function handle(): int
    {
        $this->info('Starting ATS webhook processing...');

        try {
            if ($this->option('retry')) {
                return $this->retryFailedWebhooks();
            } else {
                return $this->processPendingWebhooks();
            }
        } catch (\Exception $e) {
            $this->error('Webhook processing failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function processPendingWebhooks(): int
    {
        $query = AtsWebhook::with('atsConnection');

        if ($this->option('failed')) {
            $query->failed();
        } else {
            $query->pending();
        }

        if ($connectionId = $this->option('connection')) {
            $query->where('ats_connection_id', $connectionId);
        }

        if ($eventType = $this->option('event-type')) {
            $query->byEventType($eventType);
        }

        $limit = (int) $this->option('limit');
        $webhooks = $query->orderBy('received_at')->limit($limit)->get();

        if ($webhooks->isEmpty()) {
            $this->info('No webhooks to process.');
            return 0;
        }

        $this->info("Processing {$webhooks->count()} webhooks...");

        $processed = 0;
        $failed = 0;

        foreach ($webhooks as $webhook) {
            try {
                $this->line("Processing webhook {$webhook->id} ({$webhook->event_type_display})");

                $this->processWebhook($webhook);
                $processed++;

                $this->info("  ✅ Processed successfully");
            } catch (\Exception $e) {
                $failed++;
                $webhook->markFailed($e->getMessage());
                $this->error("  ❌ Failed: " . $e->getMessage());
            }
        }

        $this->info("Webhook processing completed:");
        $this->line("  ✅ Processed: {$processed}");
        $this->line("  ❌ Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    private function retryFailedWebhooks(): int
    {
        $query = AtsWebhook::with('atsConnection')->retryable();

        if ($connectionId = $this->option('connection')) {
            $query->where('ats_connection_id', $connectionId);
        }

        if ($eventType = $this->option('event-type')) {
            $query->byEventType($eventType);
        }

        $limit = (int) $this->option('limit');
        $webhooks = $query->orderBy('received_at')->limit($limit)->get();

        if ($webhooks->isEmpty()) {
            $this->info('No failed webhooks to retry.');
            return 0;
        }

        $this->info("Retrying {$webhooks->count()} failed webhooks...");

        $retried = 0;
        $failed = 0;

        foreach ($webhooks as $webhook) {
            try {
                $attemptNumber = $webhook->retry_count + 1;
                $this->line("Retrying webhook {$webhook->id} (attempt {$attemptNumber}/3)");

                $webhook->resetForRetry();
                $this->processWebhook($webhook);
                $retried++;

                $this->info("  ✅ Retry successful");
            } catch (\Exception $e) {
                $failed++;
                $webhook->markFailed($e->getMessage());
                $this->error("  ❌ Retry failed: " . $e->getMessage());
            }
        }

        $this->info("Webhook retry completed:");
        $this->line("  ✅ Successful retries: {$retried}");
        $this->line("  ❌ Failed retries: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    private function processWebhook(AtsWebhook $webhook): void
    {
        $connection = $webhook->atsConnection;
        $payload = $webhook->payload;

        switch ($webhook->event_type) {
            case 'job_created':
            case 'job_updated':
                $this->processJobWebhook($connection, $webhook, $payload);
                break;

            case 'application_submitted':
            case 'application_updated':
                $this->processApplicationWebhook($connection, $webhook, $payload);
                break;

            case 'candidate_created':
            case 'candidate_updated':
                $this->processCandidateWebhook($connection, $webhook, $payload);
                break;

            case 'interview_scheduled':
                $this->processInterviewWebhook($connection, $webhook, $payload);
                break;

            case 'offer_extended':
                $this->processOfferWebhook($connection, $webhook, $payload);
                break;

            case 'hire_completed':
                $this->processHireWebhook($connection, $webhook, $payload);
                break;

            default:
                $this->warn("  ⚠️  Unknown event type: {$webhook->event_type}");
                $webhook->markProcessed();
        }
    }

    private function processJobWebhook($connection, $webhook, $payload): void
    {
        // Use the ATS service to handle job webhook
        $this->atsService->processWebhook($connection, [
            'id' => $webhook->webhook_id,
            'event_type' => $webhook->event_type,
            ...$payload
        ]);

        $webhook->markProcessed();
    }

    private function processApplicationWebhook($connection, $webhook, $payload): void
    {
        // Use the ATS service to handle application webhook
        $this->atsService->processWebhook($connection, [
            'id' => $webhook->webhook_id,
            'event_type' => $webhook->event_type,
            ...$payload
        ]);

        $webhook->markProcessed();
    }

    private function processCandidateWebhook($connection, $webhook, $payload): void
    {
        // Use the ATS service to handle candidate webhook
        $this->atsService->processWebhook($connection, [
            'id' => $webhook->webhook_id,
            'event_type' => $webhook->event_type,
            ...$payload
        ]);

        $webhook->markProcessed();
    }

    private function processInterviewWebhook($connection, $webhook, $payload): void
    {
        // Handle interview scheduling webhook
        $applicationId = $payload['application_id'] ?? null;
        $interviewDate = $payload['interview_date'] ?? null;
        $interviewType = $payload['interview_type'] ?? 'phone';
        $notes = $payload['notes'] ?? '';

        if ($applicationId) {
            $application = \App\Models\AtsApplication::whereHas('jobPosting', function($query) use ($connection) {
                $query->where('ats_connection_id', $connection->id);
            })
            ->where('external_application_id', $applicationId)
            ->first();

            if ($application) {
                $application->updateStatus('interview');
                $application->addInterviewNote(
                    "Interview scheduled for {$interviewDate} ({$interviewType}). {$notes}",
                    'ATS System'
                );
            }
        }

        $webhook->markProcessed();
    }

    private function processOfferWebhook($connection, $webhook, $payload): void
    {
        // Handle offer extension webhook
        $applicationId = $payload['application_id'] ?? null;
        $offeredSalary = $payload['offered_salary'] ?? null;
        $offerDetails = $payload['offer_details'] ?? '';

        if ($applicationId) {
            $application = \App\Models\AtsApplication::whereHas('jobPosting', function($query) use ($connection) {
                $query->where('ats_connection_id', $connection->id);
            })
            ->where('external_application_id', $applicationId)
            ->first();

            if ($application) {
                $application->updateStatus('offer');

                if ($offeredSalary) {
                    $application->update(['offered_salary' => $offeredSalary]);
                }

                if ($offerDetails) {
                    $application->addInterviewNote(
                        "Offer extended: {$offerDetails}",
                        'ATS System'
                    );
                }
            }
        }

        $webhook->markProcessed();
    }

    private function processHireWebhook($connection, $webhook, $payload): void
    {
        // Handle hire completion webhook
        $applicationId = $payload['application_id'] ?? null;
        $startDate = $payload['start_date'] ?? null;
        $salary = $payload['final_salary'] ?? null;

        if ($applicationId) {
            $application = \App\Models\AtsApplication::whereHas('jobPosting', function($query) use ($connection) {
                $query->where('ats_connection_id', $connection->id);
            })
            ->where('external_application_id', $applicationId)
            ->first();

            if ($application) {
                $application->updateStatus('hired');

                if ($salary) {
                    $application->update(['offered_salary' => $salary]);
                }

                $notes = "Hire completed.";
                if ($startDate) {
                    $notes .= " Start date: {$startDate}.";
                }
                if ($salary) {
                    $notes .= " Final salary: \${$salary}.";
                }

                $application->addInterviewNote($notes, 'ATS System');
            }
        }

        $webhook->markProcessed();
    }
}