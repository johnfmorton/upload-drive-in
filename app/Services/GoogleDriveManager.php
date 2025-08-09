<?php

namespace App\Services;

use App\Models\GoogleDriveToken;
use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;
use Exception;

/**
 * Manages Google Drive OAuth, token storage, and file operations on a per-user basis.
 */
class GoogleDriveManager
{
    /**
     * Get the OAuth URL for a given user to connect their Google Drive.
     *
     * @param User $user
     * @return string
     */
    public function getAuthUrl(User $user): string
    {
        $client = $this->makeClient();
        // Use unified callback endpoint
        $client->setRedirectUri(route('google-drive.unified-callback'));

        // Add user ID as state parameter to identify user after callback
        $state = base64_encode(json_encode([
            'user_id' => $user->id,
            'user_type' => $user->role->value
        ]));
        
        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * Handle the OAuth callback and store tokens for the user.
     *
     * @param User $user
     * @param string $code
     * @return void
     * @throws Exception
     */
    public function handleCallback(User $user, string $code): void
    {
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            Log::error('Google Drive token error for user', ['user_id' => $user->id, 'error' => $token['error']]);
            throw new Exception('Failed to obtain Google Drive access token: ' . $token['error']);
        }

        // Persist or update token record
        GoogleDriveToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => json_encode($token),
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => isset($token['expires_in'])
                    ? Carbon::now()->addSeconds($token['expires_in'])
                    : null,
            ]
        );

        Log::info('Stored Google Drive tokens for user', ['user_id' => $user->id]);
    }

    /**
     * Get a configured Google API client for the specified user.
     * Automatically refreshes expired access tokens.
     *
     * @param User $user
     * @return Client
     * @throws Exception
     */
    protected function makeClientForUser(User $user): Client
    {
        $record = GoogleDriveToken::where('user_id', $user->id)->first();
        if (! $record) {
            throw new Exception('Google Drive not connected for this user.');
        }

        $client = $this->makeClient();
        $token = json_decode($record->access_token, true);
        $client->setAccessToken($token);

        // Refresh if needed
        if ($client->isAccessTokenExpired()) {
            if ($record->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken($record->refresh_token);
                $newToken = $client->getAccessToken();

                // Update stored tokens
                $record->update([
                    'access_token' => json_encode($newToken),
                    'expires_at' => isset($newToken['expires_in'])
                        ? Carbon::now()->addSeconds($newToken['expires_in'])
                        : null,
                ]);

                Log::info('Refreshed Google Drive token for user', ['user_id' => $user->id]);
            } else {
                throw new Exception('No refresh token available for user: ' . $user->id);
            }
        }

        return $client;
    }

    /**
     * Show a folder name given its ID for a specific user.
     *
     * @param User $user
     * @param string $folderId
     * @return string
     * @throws Exception
     */
    public function getFolderName(User $user, string $folderId): string
    {
        $service = new Drive($this->makeClientForUser($user));
        $folder = $service->files->get($folderId, ['fields' => 'name']);

        return $folder->getName();
    }

    /**
     * Update the root folder preference for the user.
     *
     * @param User $user
     * @param string $folderId
     * @return void
     */
    public function updateRootFolder(User $user, string $folderId): void
    {
        $user->update(['google_drive_root_folder_id' => $folderId]);
    }

    /**
     * Dispatch the upload job for the given FileUpload and user.
     *
     * @param \App\Models\FileUpload $upload
     * @param User $user
     * @return void
     */
    public function dispatchUploadJob(\App\Models\FileUpload $upload, User $user): void
    {
        // Dispatch upload job with user context
        \App\Jobs\UploadToGoogleDrive::dispatch($upload, $user);
    }

    /**
     * Create a base Google API client with application credentials.
     *
     * @return Client
     */
    protected function makeClient(): Client
    {
        $client = new Client();
        $client->setClientId(Config::get('cloud-storage.providers.google-drive.client_id'));
        $client->setClientSecret(Config::get('cloud-storage.providers.google-drive.client_secret'));
        $client->addScope(Drive::DRIVE);
        $client->addScope(Drive::DRIVE_METADATA_READONLY);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
