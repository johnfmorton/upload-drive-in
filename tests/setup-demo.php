<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\SetupService;
use App\Helpers\SetupHelper;

// Create a simple demonstration of the setup state management
echo "=== Setup State Management Demo ===\n\n";

// Test the service directly
$setupService = new SetupService();

echo "1. Initial setup state:\n";
echo "   - Setup required: " . ($setupService->isSetupRequired() ? 'YES' : 'NO') . "\n";
echo "   - Current step: " . $setupService->getSetupStep() . "\n";
echo "   - Progress: " . $setupService->getSetupProgress() . "%\n\n";

echo "2. Using helper functions:\n";
echo "   - is_setup_required(): " . (is_setup_required() ? 'YES' : 'NO') . "\n";
echo "   - get_setup_step(): " . get_setup_step() . "\n";
echo "   - should_bypass_setup('/setup/welcome'): " . (should_bypass_setup(null, '/setup/welcome') ? 'YES' : 'NO') . "\n";
echo "   - should_bypass_setup('/admin'): " . (should_bypass_setup(null, '/admin') ? 'YES' : 'NO') . "\n\n";

echo "3. Step information:\n";
foreach (['welcome', 'database', 'admin', 'storage', 'complete'] as $step) {
    echo "   - {$step}: " . SetupHelper::getStepDisplayName($step) . " - " . SetupHelper::getStepDescription($step) . "\n";
}

echo "\n4. Testing step updates:\n";
$setupService->updateSetupStep('welcome', true);
echo "   - Marked 'welcome' as complete\n";
echo "   - New progress: " . $setupService->getSetupProgress() . "%\n";
echo "   - Current step: " . $setupService->getSetupStep() . "\n";

echo "\n=== Demo Complete ===\n";