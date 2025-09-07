<?php

namespace App\Console\Commands;

use App\Services\VerificationMailFactory;
use Illuminate\Console\Command;

class EmailVerificationMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:verification-metrics 
                            {--role= : Filter by specific role (admin, employee, client)}
                            {--event= : Filter by specific event type}
                            {--daily : Show only today\'s metrics}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display email verification metrics by role and event type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mailFactory = app(VerificationMailFactory::class);
        
        $role = $this->option('role');
        $event = $this->option('event');
        $dailyOnly = $this->option('daily');
        $jsonOutput = $this->option('json');
        
        // Validate role option
        if ($role && !in_array($role, ['admin', 'employee', 'client', 'unknown'])) {
            $this->error('Invalid role. Valid roles are: admin, employee, client, unknown');
            return 1;
        }
        
        // Validate event option
        $validEvents = [
            'template_selected', 
            'template_creation_error', 
            'email_sent', 
            'email_send_error',
            'verification_success', 
            'verification_failure'
        ];
        
        if ($event && !in_array($event, $validEvents)) {
            $this->error('Invalid event. Valid events are: ' . implode(', ', $validEvents));
            return 1;
        }
        
        // Get metrics
        $metrics = $mailFactory->getMetrics($role, $event, $dailyOnly);
        
        if ($jsonOutput) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }
        
        // Display metrics in table format
        $this->displayMetrics($metrics, $dailyOnly);
        
        return 0;
    }
    
    /**
     * Display metrics in a formatted table.
     *
     * @param array $metrics
     * @param bool $dailyOnly
     * @return void
     */
    private function displayMetrics(array $metrics, bool $dailyOnly): void
    {
        $title = $dailyOnly ? 'Email Verification Metrics (Today)' : 'Email Verification Metrics (All Time)';
        $this->info($title);
        $this->line(str_repeat('=', strlen($title)));
        
        foreach ($metrics as $role => $events) {
            $this->newLine();
            $this->line("<fg=yellow>Role: {$role}</>");
            $this->line(str_repeat('-', 40));
            
            $tableData = [];
            foreach ($events as $event => $count) {
                $tableData[] = [
                    'Event' => $event,
                    'Count' => number_format($count)
                ];
            }
            
            if (!empty($tableData)) {
                $this->table(['Event', 'Count'], $tableData);
            } else {
                $this->line('No data available');
            }
        }
        
        // Calculate and display success rates
        $this->displaySuccessRates($metrics);
    }
    
    /**
     * Display success rates for email verification.
     *
     * @param array $metrics
     * @return void
     */
    private function displaySuccessRates(array $metrics): void
    {
        $this->newLine();
        $this->info('Success Rates');
        $this->line(str_repeat('=', 13));
        
        $successRateData = [];
        
        foreach ($metrics as $role => $events) {
            $emailsSent = $events['email_sent'] ?? 0;
            $emailErrors = $events['email_send_error'] ?? 0;
            $verificationSuccess = $events['verification_success'] ?? 0;
            $verificationFailure = $events['verification_failure'] ?? 0;
            
            // Email delivery success rate
            $totalEmailAttempts = $emailsSent + $emailErrors;
            $emailSuccessRate = $totalEmailAttempts > 0 ? ($emailsSent / $totalEmailAttempts) * 100 : 0;
            
            // Verification completion success rate
            $totalVerificationAttempts = $verificationSuccess + $verificationFailure;
            $verificationSuccessRate = $totalVerificationAttempts > 0 ? ($verificationSuccess / $totalVerificationAttempts) * 100 : 0;
            
            $successRateData[] = [
                'Role' => $role,
                'Email Delivery' => $totalEmailAttempts > 0 ? sprintf('%.1f%% (%d/%d)', $emailSuccessRate, $emailsSent, $totalEmailAttempts) : 'N/A',
                'Verification Completion' => $totalVerificationAttempts > 0 ? sprintf('%.1f%% (%d/%d)', $verificationSuccessRate, $verificationSuccess, $totalVerificationAttempts) : 'N/A'
            ];
        }
        
        if (!empty($successRateData)) {
            $this->table(['Role', 'Email Delivery', 'Verification Completion'], $successRateData);
        }
        
        $this->newLine();
        $this->line('<fg=gray>Use --daily flag to see today\'s metrics only</>');
        $this->line('<fg=gray>Use --role=<role> to filter by specific role</>');
        $this->line('<fg=gray>Use --event=<event> to filter by specific event</>');
        $this->line('<fg=gray>Use --json for JSON output</>');
    }
}
