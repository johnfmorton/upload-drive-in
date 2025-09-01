<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy {--skip-assets : Skip building frontend assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the application with proper cache clearing and optimization';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Laravel deployment...');

        // Step 1: Clear all caches
        $this->info('📝 Clearing application caches...');
        $this->clearCaches();

        // Step 2: Run database migrations
        $this->info('🗄️ Running database migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        // Step 3: Optimize for production
        $this->info('⚡ Optimizing for production...');
        $this->optimizeForProduction();

        // Step 4: Build frontend assets (optional)
        if (!$this->option('skip-assets')) {
            $this->info('🎨 Building frontend assets...');
            $this->buildAssets();
        }

        // Step 5: Queue restart
        $this->info('🔄 Restarting queue workers...');
        Artisan::call('queue:restart');

        $this->info('✅ Deployment completed successfully!');
        
        return Command::SUCCESS;
    }

    /**
     * Clear all application caches
     */
    private function clearCaches(): void
    {
        $caches = ['config', 'route', 'view', 'cache'];
        
        foreach ($caches as $cache) {
            Artisan::call("{$cache}:clear");
            $this->line("  ✓ {$cache} cache cleared");
        }
    }

    /**
     * Optimize application for production
     */
    private function optimizeForProduction(): void
    {
        $optimizations = ['config', 'route', 'view'];
        
        foreach ($optimizations as $optimization) {
            Artisan::call("{$optimization}:cache");
            $this->line("  ✓ {$optimization} cached");
        }
    }

    /**
     * Build frontend assets
     */
    private function buildAssets(): void
    {
        if (file_exists(base_path('package.json'))) {
            exec('npm ci && npm run build', $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->line('  ✓ Frontend assets built successfully');
            } else {
                $this->warn('  ⚠ Failed to build frontend assets');
            }
        } else {
            $this->line('  ℹ No package.json found, skipping asset build');
        }
    }
}