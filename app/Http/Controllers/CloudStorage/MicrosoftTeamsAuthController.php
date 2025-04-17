<?php

namespace App\Http\Controllers\CloudStorage;

use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicrosoftTeamsAuthController extends CloudStorageAuthController
{
    protected function getProviderName(): string
    {
        return 'microsoft-teams';
    }

    public function connect()
    {
        $clientId = config("{$this->getConfigKey()}.client_id");
        $redirectUri = config("{$this->getConfigKey()}.redirect_uri");

        $authUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?"
            . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'scope' => 'offline_access Files.ReadWrite.All',
                'response_mode' => 'query'
            ]);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            Log::error('Microsoft Teams callback received no authorization code');
            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Microsoft Teams: No authorization code received.');
        }

        try {
            Log::info('Microsoft Teams callback received', ['code' => $request->code]);

            $clientId = config("{$this->getConfigKey()}.client_id");
            $clientSecret = config("{$this->getConfigKey()}.client_secret");
            $redirectUri = config("{$this->getConfigKey()}.redirect_uri");

            // Exchange the authorization code for an access token
            $client = new Client();
            $response = $client->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $request->code,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $token = json_decode($response->getBody()->getContents(), true);
            $token['expires_at'] = time() + $token['expires_in'];

            if (!isset($token['error'])) {
                Log::info('Token received successfully', ['token_type' => $token['token_type']]);

                // Store the token
                $this->saveCredentials($token);

                // Test the connection
                $graph = new Graph();
                $graph->setAccessToken($token['access_token']);

                // Test access to the root folder
                $rootFolderId = config("{$this->getConfigKey()}.root_folder_id");
                $graph->createRequest('GET', "/drives/{$rootFolderId}")
                    ->execute();

                Log::info('Successfully verified root folder access', ['folder_id' => $rootFolderId]);

                return redirect($this->getSuccessRedirect())
                    ->with('success', 'Microsoft Teams connected successfully.');
            }

            Log::error('Failed to obtain Microsoft Teams token', ['error' => $token['error'] ?? 'Unknown error']);
            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Microsoft Teams: Unable to obtain access token.');

        } catch (\Exception $e) {
            Log::error('Microsoft Teams connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Microsoft Teams: ' . $e->getMessage());
        }
    }
}
