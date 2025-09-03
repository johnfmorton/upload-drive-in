<?php

namespace App\Console\Commands;

use App\Services\CloudStorageProviderAvailabilityService;
use Illuminate\Console\Command;

class CheckProviderAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:check-provider-availability 
                            {--provider= : Check specific provider availability}
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check cloud storage provider availability status';

    /**
     * Execute the console command.
     */
    public function handle(CloudStorageProviderAvailabilityService $availabilityService): int
    {
        $provider = $this->option('provider');
        $format = $this->option('format');

        if ($provider) {
            return $this->checkSpecificProvider($availabilityService, $provider, $format);
        }

        return $this->checkAllProviders($availabilityService, $format);
    }

    /**
     * Check availability of a specific provider
     */
    private function checkSpecificProvider(CloudStorageProviderAvailabilityService $service, string $provider, string $format): int
    {
        $status = $service->getProviderAvailabilityStatusEnum($provider);
        $isSelectable = $service->isValidProviderSelection($provider);
        $isDefault = $service->getDefaultProvider() === $provider;

        if ($format === 'json') {
            $this->line(json_encode([
                'provider' => $provider,
                'status' => $status->value,
                'status_label' => $status->label(),
                'selectable' => $isSelectable,
                'default' => $isDefault,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->info("Provider: {$provider}");
            $this->line("Status: {$status->label()} ({$status->value})");
            $this->line("Selectable: " . ($isSelectable ? 'Yes' : 'No'));
            $this->line("Default: " . ($isDefault ? 'Yes' : 'No'));
        }

        return Command::SUCCESS;
    }

    /**
     * Check availability of all providers
     */
    private function checkAllProviders(CloudStorageProviderAvailabilityService $service, string $format): int
    {
        $providersWithStatus = $service->getAllProvidersWithStatus();
        $defaultProvider = $service->getDefaultProvider();

        if ($format === 'json') {
            $data = $providersWithStatus->map(function ($data, $provider) use ($defaultProvider) {
                return [
                    'provider' => $provider,
                    'status' => $data['status']->value,
                    'status_label' => $data['status_label'],
                    'selectable' => $data['selectable'],
                    'visible' => $data['visible'],
                    'default' => $provider === $defaultProvider,
                ];
            })->values();

            $this->line(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $tableData = [];
            
            foreach ($providersWithStatus as $provider => $data) {
                $tableData[] = [
                    'Provider' => $data['label'],
                    'Status' => $data['status_label'],
                    'Selectable' => $data['selectable'] ? '✓' : '✗',
                    'Visible' => $data['visible'] ? '✓' : '✗',
                    'Default' => $provider === $defaultProvider ? '✓' : '✗',
                ];
            }

            $this->table(
                ['Provider', 'Status', 'Selectable', 'Visible', 'Default'],
                $tableData
            );

            // Summary
            $availableCount = $service->getAvailableProviders();
            $comingSoonCount = $service->getComingSoonProviders();
            
            $this->newLine();
            $this->info("Summary:");
            $this->line("Available providers: " . count($availableCount));
            $this->line("Coming soon providers: " . count($comingSoonCount));
            $this->line("Default provider: " . ($defaultProvider ?? 'None'));
        }

        return Command::SUCCESS;
    }
}