<?php

/**
 * Manual Testing Scenarios for Google Drive Token Refresh Validation
 * 
 * This file contains manual testing scenarios to verify that the Google Drive
 * token refresh mechanism works correctly in various edge cases.
 * 
 * Run this script with: ddev artisan tinker --execute="require 'tests/manual/google-drive-token-refresh-manual-test.php';"
 */

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;

echo "=== Google Drive Token Refresh Manual Testing Scenarios ===\n\n";

// Create test user
$testUser = User::factory()->create([
    'name' => 'Token Test User',
    'email' => 'token-test@example.com',
    'role' => 'admin',
]);

echo "Created test user: {$testUser->email} (ID: {$testUser->id})\n\n";

// Initialize services
$healthService = new CloudStorageHealthService();
$driveService = new GoogleDriveService();

echo "=== SCENARIO 1: Expired Access Token with Valid Refresh Token ===\n";
echo "This tests the most common scenario where access tokens expire but refresh tokens are still valid.\n\n";

// Clean up any existing tokens
GoogleDriveToken::where('user_id', $testUser->id)->delete();
CloudStorageHealthStatus::where('user_id', $testUser->id)->delete();

// Create expired access token with valid refresh token
$expiredToken = GoogleDriveToken::create([
    'user_id' => $testUser->id,
    'access_token' => 'expired_access_token_' . time(),
    'refresh_token' => 'valid_refresh_token_' . time(),
    'expires_at' => Carbon::now()->subMinutes(30),
    'token_type' => 'Bearer',
    'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
]);

echo "✓ Created expired access token (expired 30 minutes ago)\n";
echo "  - Access Token: {$expiredToken->access_token}\n";
echo "  - Refresh Token: {$expiredToken->refresh_token}\n";
echo "  - Expires At: {$expiredToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Visit: " . config('app.url') . "/admin/dashboard\n";
echo "2. Login as: {$testUser->email}\n";
echo "3. Observe the Google Drive status widget\n";
echo "4. Click 'Test Connection' button\n";
echo "5. Expected Result: Status should show 'Healthy' after automatic token refresh\n";
echo "6. Expected Result: No 'Token refresh needed' warnings should appear\n\n";

echo "=== SCENARIO 2: Both Access and Refresh Tokens Expired ===\n";
echo "This tests the scenario where both tokens are expired and user needs to reconnect.\n\n";

// Update token to have expired refresh token
$expiredToken->update([
    'access_token' => 'expired_access_token_both_' . time(),
    'refresh_token' => 'expired_refresh_token_' . time(),
    'expires_at' => Carbon::now()->subDays(30), // Very old expiration
]);

echo "✓ Updated token to have both access and refresh tokens expired\n";
echo "  - Access Token: {$expiredToken->access_token}\n";
echo "  - Refresh Token: {$expiredToken->refresh_token}\n";
echo "  - Expires At: {$expiredToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Refresh the dashboard page\n";
echo "2. Click 'Test Connection' button\n";
echo "3. Expected Result: Status should show 'Authentication Required'\n";
echo "4. Expected Result: 'Reconnect' button should be available\n";
echo "5. Expected Result: No confusing 'Token refresh needed' messages\n\n";

echo "=== SCENARIO 3: Token Expires During Active Session ===\n";
echo "This tests the scenario where a token expires while the user is actively using the system.\n\n";

// Create token that expires very soon
$soonToExpireToken = GoogleDriveToken::updateOrCreate(
    ['user_id' => $testUser->id],
    [
        'access_token' => 'soon_to_expire_token_' . time(),
        'refresh_token' => 'valid_refresh_for_soon_expire_' . time(),
        'expires_at' => Carbon::now()->addMinutes(2), // Expires in 2 minutes
        'token_type' => 'Bearer',
        'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
    ]
);

echo "✓ Created token that expires in 2 minutes\n";
echo "  - Access Token: {$soonToExpireToken->access_token}\n";
echo "  - Refresh Token: {$soonToExpireToken->refresh_token}\n";
echo "  - Expires At: {$soonToExpireToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Refresh the dashboard page\n";
echo "2. Observe initial status (should be 'Healthy')\n";
echo "3. Wait 3 minutes for token to expire\n";
echo "4. Click 'Test Connection' button\n";
echo "5. Expected Result: Status should remain 'Healthy' after automatic refresh\n";
echo "6. Expected Result: No token expiration warnings during the process\n\n";

echo "=== SCENARIO 4: Multiple Rapid Status Checks ===\n";
echo "This tests how the system handles multiple rapid status checks with token refresh.\n\n";

// Reset to expired token for this test
$rapidTestToken = GoogleDriveToken::updateOrCreate(
    ['user_id' => $testUser->id],
    [
        'access_token' => 'rapid_test_expired_' . time(),
        'refresh_token' => 'rapid_test_refresh_' . time(),
        'expires_at' => Carbon::now()->subMinutes(15),
        'token_type' => 'Bearer',
        'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
    ]
);

echo "✓ Created expired token for rapid testing\n";
echo "  - Access Token: {$rapidTestToken->access_token}\n";
echo "  - Refresh Token: {$rapidTestToken->refresh_token}\n";
echo "  - Expires At: {$rapidTestToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Refresh the dashboard page\n";
echo "2. Rapidly click 'Test Connection' button 5 times in quick succession\n";
echo "3. Expected Result: System should handle rapid clicks gracefully\n";
echo "4. Expected Result: Only one token refresh should occur\n";
echo "5. Expected Result: Final status should be 'Healthy'\n";
echo "6. Expected Result: No duplicate refresh attempts or errors\n\n";

echo "=== SCENARIO 5: Network Issues During Token Refresh ===\n";
echo "This tests how the system handles network issues during token refresh attempts.\n\n";

echo "Manual Test Steps:\n";
echo "1. Disconnect your internet connection temporarily\n";
echo "2. Refresh the dashboard page\n";
echo "3. Click 'Test Connection' button\n";
echo "4. Expected Result: Status should show 'Connection Issues'\n";
echo "5. Reconnect internet and click 'Test Connection' again\n";
echo "6. Expected Result: Status should recover to 'Healthy'\n\n";

echo "=== SCENARIO 6: Token Refresh During File Upload ===\n";
echo "This tests automatic token refresh during actual file operations.\n\n";

// Create a valid token that will be used for upload test
$uploadTestToken = GoogleDriveToken::updateOrCreate(
    ['user_id' => $testUser->id],
    [
        'access_token' => 'upload_test_valid_' . time(),
        'refresh_token' => 'upload_test_refresh_' . time(),
        'expires_at' => Carbon::now()->addHours(1),
        'token_type' => 'Bearer',
        'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
    ]
);

echo "✓ Created valid token for upload testing\n";
echo "  - Access Token: {$uploadTestToken->access_token}\n";
echo "  - Refresh Token: {$uploadTestToken->refresh_token}\n";
echo "  - Expires At: {$uploadTestToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Visit the public upload page: " . config('app.url') . "\n";
echo "2. Upload a test file\n";
echo "3. Monitor the upload process in the admin dashboard\n";
echo "4. Manually expire the token in the database during upload:\n";
echo "   GoogleDriveToken::where('user_id', {$testUser->id})->update(['expires_at' => now()->subMinutes(30)]);\n";
echo "5. Expected Result: Upload should complete successfully with automatic token refresh\n";
echo "6. Expected Result: No upload failures due to token expiration\n\n";

echo "=== SCENARIO 7: Status Consistency Across Multiple Browser Tabs ===\n";
echo "This tests status consistency when multiple tabs are open.\n\n";

echo "Manual Test Steps:\n";
echo "1. Open the admin dashboard in two browser tabs\n";
echo "2. In tab 1, click 'Test Connection' button\n";
echo "3. In tab 2, observe if status updates automatically\n";
echo "4. Expected Result: Both tabs should show consistent status\n";
echo "5. Expected Result: No conflicting status messages between tabs\n\n";

echo "=== SCENARIO 8: Edge Case - Token Expires Exactly During Status Check ===\n";
echo "This tests the edge case where a token expires exactly during a status check.\n\n";

// Create token that expires in 30 seconds
$edgeCaseToken = GoogleDriveToken::updateOrCreate(
    ['user_id' => $testUser->id],
    [
        'access_token' => 'edge_case_token_' . time(),
        'refresh_token' => 'edge_case_refresh_' . time(),
        'expires_at' => Carbon::now()->addSeconds(30),
        'token_type' => 'Bearer',
        'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
    ]
);

echo "✓ Created token that expires in 30 seconds\n";
echo "  - Access Token: {$edgeCaseToken->access_token}\n";
echo "  - Refresh Token: {$edgeCaseToken->refresh_token}\n";
echo "  - Expires At: {$edgeCaseToken->expires_at}\n\n";

echo "Manual Test Steps:\n";
echo "1. Refresh the dashboard page\n";
echo "2. Wait exactly 30 seconds\n";
echo "3. Immediately click 'Test Connection' button\n";
echo "4. Expected Result: Status check should handle the expiration gracefully\n";
echo "5. Expected Result: Automatic refresh should occur and status should be 'Healthy'\n\n";

echo "=== VERIFICATION COMMANDS ===\n";
echo "Use these commands in tinker to verify token states during testing:\n\n";

echo "// Check current token status\n";
echo "GoogleDriveToken::where('user_id', {$testUser->id})->first();\n\n";

echo "// Check health status\n";
echo "CloudStorageHealthStatus::where('user_id', {$testUser->id})->where('provider', 'google-drive')->first();\n\n";

echo "// Manually expire token for testing\n";
echo "GoogleDriveToken::where('user_id', {$testUser->id})->update(['expires_at' => now()->subMinutes(30)]);\n\n";

echo "// Reset token to valid state\n";
echo "GoogleDriveToken::where('user_id', {$testUser->id})->update(['expires_at' => now()->addHours(1)]);\n\n";

echo "// Test token refresh programmatically\n";
echo "\$service = new App\\Services\\GoogleDriveService();\n";
echo "\$result = \$service->validateAndRefreshToken(User::find({$testUser->id}));\n";
echo "echo \$result ? 'Token refresh successful' : 'Token refresh failed';\n\n";

echo "// Test API connectivity\n";
echo "\$service = new App\\Services\\GoogleDriveService();\n";
echo "\$result = \$service->testApiConnectivity(User::find({$testUser->id}));\n";
echo "echo \$result ? 'API connectivity successful' : 'API connectivity failed';\n\n";

echo "// Run complete health check\n";
echo "\$healthService = new App\\Services\\CloudStorageHealthService();\n";
echo "\$status = \$healthService->checkConnectionHealth(User::find({$testUser->id}), 'google-drive');\n";
echo "echo 'Status: ' . \$status->consolidated_status;\n";
echo "echo 'Message: ' . \$status->getConsolidatedStatusMessage();\n\n";

echo "=== CLEANUP ===\n";
echo "After testing, clean up the test data:\n\n";
echo "// Remove test user and related data\n";
echo "GoogleDriveToken::where('user_id', {$testUser->id})->delete();\n";
echo "CloudStorageHealthStatus::where('user_id', {$testUser->id})->delete();\n";
echo "User::find({$testUser->id})->delete();\n\n";

echo "=== EXPECTED OUTCOMES SUMMARY ===\n";
echo "After completing all scenarios, verify:\n";
echo "✓ Expired access tokens with valid refresh tokens result in 'Healthy' status\n";
echo "✓ Expired refresh tokens result in 'Authentication Required' status\n";
echo "✓ No 'Token refresh needed' warnings appear when refresh works automatically\n";
echo "✓ Status messages are consistent across admin and employee dashboards\n";
echo "✓ Multiple rapid status checks are handled gracefully\n";
echo "✓ Network issues during refresh show 'Connection Issues' status\n";
echo "✓ File operations trigger automatic token refresh when needed\n";
echo "✓ Status updates are consistent across multiple browser tabs\n";
echo "✓ Edge cases with precise timing are handled correctly\n\n";

echo "Manual testing scenarios created successfully!\n";
echo "Test user credentials: {$testUser->email} / password (use factory default)\n";
echo "Dashboard URL: " . config('app.url') . "/admin/dashboard\n";