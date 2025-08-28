<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\FileUpload;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create test users
$admin = User::where('role', 'admin')->first();
if (!$admin) {
    $admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin@test.com',
        'name' => 'Test Admin'
    ]);
}

$client = User::where('role', 'client')->first();
if (!$client) {
    $client = User::factory()->create([
        'role' => 'client',
        'email' => 'client@test.com',
        'name' => 'Test Client'
    ]);
}

// Create test files
$testFiles = [
    [
        'filename' => 'test-image.jpg',
        'original_filename' => 'test-image.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024000,
        'email' => 'client@test.com'
    ],
    [
        'filename' => 'test-document.pdf',
        'original_filename' => 'test-document.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2048000,
        'email' => 'client@test.com'
    ],
    [
        'filename' => 'test-text.txt',
        'original_filename' => 'test-text.txt',
        'mime_type' => 'text/plain',
        'file_size' => 1024,
        'email' => 'client@test.com'
    ]
];

foreach ($testFiles as $fileData) {
    FileUpload::firstOrCreate(
        ['filename' => $fileData['filename']],
        array_merge($fileData, [
            'client_user_id' => $client->id,
            'company_user_id' => $admin->id,
            'storage_provider' => 'local',
            'validation_method' => 'email',
            'chunk_size' => 1048576,
            'total_chunks' => 1
        ])
    );
}

echo "Test data created successfully!\n";
echo "Admin: {$admin->email}\n";
echo "Client: {$client->email}\n";
echo "Files created: " . FileUpload::count() . "\n";