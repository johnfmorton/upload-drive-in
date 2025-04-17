<?php

namespace App\Http\Controllers\CloudStorage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

abstract class CloudStorageAuthController extends Controller
{
    /**
     * Get the provider name.
     *
     * @return string
     */
    abstract protected function getProviderName(): string;

    /**
     * Get the configuration key for this provider.
     *
     * @return string
     */
    protected function getConfigKey(): string
    {
        return "cloud-storage.providers.{$this->getProviderName()}";
    }

    /**
     * Get the credentials file path for this provider.
     *
     * @return string
     */
    protected function getCredentialsPath(): string
    {
        return "{$this->getProviderName()}-credentials.json";
    }

    /**
     * Save the credentials to storage.
     *
     * @param array $credentials
     * @return bool
     */
    protected function saveCredentials(array $credentials): bool
    {
        return Storage::put($this->getCredentialsPath(), json_encode($credentials));
    }

    /**
     * Delete the credentials from storage.
     *
     * @return bool
     */
    protected function deleteCredentials(): bool
    {
        return Storage::delete($this->getCredentialsPath());
    }

    /**
     * Get the redirect URL after successful connection.
     *
     * @return string
     */
    protected function getSuccessRedirect(): string
    {
        return route('admin.dashboard');
    }

    /**
     * Get the redirect URL after disconnection.
     *
     * @return string
     */
    protected function getDisconnectRedirect(): string
    {
        return route('admin.dashboard');
    }

    /**
     * Get the redirect URL after failed connection.
     *
     * @return string
     */
    protected function getFailureRedirect(): string
    {
        return route('admin.dashboard');
    }

    /**
     * Handle the OAuth callback.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    abstract public function callback(Request $request);

    /**
     * Initiate the OAuth connection.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    abstract public function connect();

    /**
     * Disconnect the provider.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect()
    {
        $this->deleteCredentials();
        return redirect($this->getDisconnectRedirect())
            ->with('success', ucfirst($this->getProviderName()) . ' disconnected successfully.');
    }
}
