<?php

namespace App\Console\Commands;

use App\Services\DomainRulesCacheService;
use Illuminate\Console\Command;

class DomainRulesCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain-rules:cache 
                            {action : The action to perform (stats, clear, warm)}
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage domain rules cache (view stats, clear, or warm up)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cacheService = app(DomainRulesCacheService::class);
        $action = $this->argument('action');
        $format = $this->option('format');

        switch ($action) {
            case 'stats':
                $this->displayStats($cacheService, $format);
                break;
            
            case 'clear':
                $cacheService->clearCache();
                $this->info(__('messages.domain_rules_cache_cleared'));
                break;
            
            case 'warm':
                $cacheService->warmCache();
                $this->info(__('messages.domain_rules_cache_warmed'));
                break;
            
            default:
                $this->error(__('messages.domain_rules_cache_command_invalid_action'));
                return 1;
        }

        return 0;
    }

    /**
     * Display cache statistics
     *
     * @param DomainRulesCacheService $cacheService
     * @param string $format
     * @return void
     */
    private function displayStats(DomainRulesCacheService $cacheService, string $format): void
    {
        $stats = $cacheService->getCacheStats();

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return;
        }

        // Display as table
        $this->info(__('messages.domain_rules_cache_statistics'));
        $this->newLine();

        $tableData = [
            [__('messages.cache_key'), $stats['cache_key']],
            [__('messages.cache_ttl'), $stats['cache_ttl'] . ' ' . __('messages.domain_rules_cache_command_seconds')],
            [__('messages.cache_hit'), $stats['cache_hit'] ? __('messages.domain_rules_cache_command_yes') : __('messages.domain_rules_cache_command_no')],
            [__('messages.rules_loaded'), $stats['rules_loaded'] ? __('messages.domain_rules_cache_command_yes') : __('messages.domain_rules_cache_command_no')],
        ];

        if ($stats['rules_config']) {
            $tableData[] = [__('messages.rules_mode'), $stats['rules_config']['mode']];
            $tableData[] = [__('messages.rules_count'), $stats['rules_config']['rules_count']];
        }

        $this->table([
            __('messages.domain_rules_cache_command_property'), 
            __('messages.domain_rules_cache_command_value')
        ], $tableData);
    }
}