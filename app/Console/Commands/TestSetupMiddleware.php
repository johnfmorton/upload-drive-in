<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SetupService;

class TestSetupMiddleware extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:setup-middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the setup middleware logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setupService = app(SetupService::class);
        
        $this->info('Testing setup service...');
        
        try {
            $isRequired = $setupService->isSetupRequired();
            $this->info('Setup required: ' . ($isRequired ? 'YES' : 'NO'));
            
            if ($isRequired) {
                $step = $setupService->getSetupStep();
                $this->info('Current step: ' . $step);
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
    }
}
