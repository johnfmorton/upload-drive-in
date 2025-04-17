<?php

namespace App\Http\Controllers\CloudStorage;

use Illuminate\Http\Request;
use Spatie\Dropbox\Client as DropboxClient;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DropboxAuthController extends CloudStorageAuthController
{
    protected function getProviderName(): string
    {
        return 'dropbox';
    }

    public function connect()
    {
        $clientId = config("{$this->getConfigKey()}.client_id");
        $redirectUri = config("{$this->getConfigKey()}.redirect_uri");

        $authUrl = "https://www.dropbox.com/oauth2/authorize?"
            . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'token_access_type' => 'offline',
                'scope' => 'files.content.read files.content.write files.metadata.read'
            ]);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            Log::error('Dropbox callback received no authorization code');
            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Dropbox: No authorization code received.');
        }

        try {
            Log::info('Dropbox callback received', ['code' => $request->code]);

            $clientId = config("{$this->getConfigKey()}.client_id");
            $clientSecret = config("{$this->getConfigKey()}.client_secret");
            $redirectUri = config("{$this->getConfigKey()}.redirect_uri");

            // Exchange the authorization code for an access token
            $client = new Client();
            $response = $client->post('https://api.dropboxapi.com/oauth2/token', [
                'form_params' => [
                    'code' => $request->code,
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            $token = json_decode($response->getBody()->getContents(), true);
            $token['expires_at'] = time() + $token['expires_in'];

            if (!isset($token['error'])) {
                Log::info('Token received successfully', ['token_type' => $token['token_type']]);

                // Store the token
                $this->saveCredentials($token);

                // Test the connection
                $dropbox = new DropboxClient($token['access_token']);

                // Test access by listing the root folder
                $dropbox->listFolder('');

                Log::info('Successfully verified root folder access');

                return redirect($this->getSuccessRedirect())
                    ->with('success', 'Dropbox connected successfully.');
            }

            Log::error('Failed to obtain Dropbox token', ['error' => $token['error'] ?? 'Unknown error']);
            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Dropbox: Unable to obtain access token.');

        } catch (\Exception $e) {
            Log::error('Dropbox connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect($this->getFailureRedirect())
                ->with('error', 'Failed to connect Dropbox: ' . $e->getMessage());
        }
    }
}
