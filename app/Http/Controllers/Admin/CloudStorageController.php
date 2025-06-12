<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Auth;
use App\Services\GoogleDriveService;

class CloudStorageController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Display the cloud storage configuration page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $currentFolderId = config('cloud-storage.providers.google-drive.root_folder_id');
        $currentFolderName = '';

        try {
            if ($user->hasGoogleDriveConnected()) {
                $service = $this->driveService->getDriveService($user);
                if (!empty($currentFolderId)) {
                    $folder = $service->files->get($currentFolderId, ['fields' => 'id,name']);
                    $currentFolderName = $folder->getName();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Google Drive folder name', ['error' => $e->getMessage()]);
        }

        return view('admin.cloud-storage.index', compact('currentFolderId', 'currentFolderName'));
    }

    /**
     * Update Microsoft Teams configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMicrosoftTeams(Request $request)
    {
        $validated = $request->validate([
            'microsoft_teams_client_id' => ['required', 'string'],
            'microsoft_teams_client_secret' => ['nullable', 'string'],
            'microsoft_teams_root_folder_id' => ['required', 'string'],
        ]);

        try {
            // Save Microsoft Teams credentials into .env
            $this->updateEnvironmentValue('MICROSOFT_TEAMS_CLIENT_ID', $validated['microsoft_teams_client_id']);
            if (!empty($validated['microsoft_teams_client_secret'])) {
                $this->updateEnvironmentValue('MICROSOFT_TEAMS_CLIENT_SECRET', $validated['microsoft_teams_client_secret']);
            }
            $this->updateEnvironmentValue('MICROSOFT_TEAMS_ROOT_FOLDER_ID', $validated['microsoft_teams_root_folder_id']);

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Microsoft Teams environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Microsoft Teams environment variables', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Dropbox configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDropbox(Request $request)
    {
        $validated = $request->validate([
            'dropbox_client_id' => ['required', 'string'],
            'dropbox_client_secret' => ['nullable', 'string'],
            'dropbox_root_folder' => ['required', 'string'],
        ]);

        try {
            // Save Dropbox credentials into .env
            $this->updateEnvironmentValue('DROPBOX_CLIENT_ID', $validated['dropbox_client_id']);
            if (!empty($validated['dropbox_client_secret'])) {
                $this->updateEnvironmentValue('DROPBOX_CLIENT_SECRET', $validated['dropbox_client_secret']);
            }
            $this->updateEnvironmentValue('DROPBOX_ROOT_FOLDER', $validated['dropbox_root_folder']);

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Dropbox environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Dropbox environment variables', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Google Drive configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDrive(Request $request)
    {
        $validated = $request->validate([
            'google_drive_client_id' => ['required', 'string'],
            'google_drive_client_secret' => ['nullable', 'string'],
            'google_drive_root_folder_id' => ['required', 'string'],
        ]);

        try {
            // Save Google Drive credentials into .env
            $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_ID', $validated['google_drive_client_id']);
            if (!empty($validated['google_drive_client_secret'])) {
                $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_SECRET', $validated['google_drive_client_secret']);
            }
            $this->updateEnvironmentValue('GOOGLE_DRIVE_ROOT_FOLDER_ID', $validated['google_drive_root_folder_id']);

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Google Drive environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive environment variables', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update the default storage provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDefault(Request $request)
    {
        $validated = $request->validate([
            'default_provider' => ['required', 'string', 'in:google-drive,microsoft-teams,dropbox'],
        ]);

        try {
            // Save default provider into .env
            $this->updateEnvironmentValue('CLOUD_STORAGE_DEFAULT', $validated['default_provider']);

            // Clear config cache so new default is applied
            Artisan::call('config:clear');

            Log::info('Default storage provider environment variable updated successfully', [
                'provider' => $validated['default_provider'],
            ]);

            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update default storage provider environment variable', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Save Google Drive client ID and secret to .env.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDriveCredentials(Request $request)
    {
        $validated = $request->validate([
            'google_drive_client_id' => ['required', 'string'],
            'google_drive_client_secret' => ['nullable', 'string'],
        ]);

        try {
            $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_ID', $validated['google_drive_client_id']);
            if (!empty($validated['google_drive_client_secret'])) {
                $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_SECRET', $validated['google_drive_client_secret']);
            }
            Artisan::call('config:clear');
            Log::info('Google Drive credentials updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive credentials', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Save credentials and redirect user to Google Drive OAuth.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAndConnectGoogleDrive(Request $request)
    {
        $clientId = config('cloud-storage.providers.google-drive.client_id');
        if (empty($clientId)) {
            return redirect()->back()->with('error', __('messages.client_id').' '.__('messages.error_generic'));
        }

        try {
            $authUrl = $this->driveService->getAuthUrl(Auth::user());
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to initiate Google Drive connection', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Google Drive root folder ID in .env.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDriveRootFolder(Request $request)
    {
        $validated = $request->validate([
            'google_drive_root_folder_id' => ['required', 'string'],
        ]);

        try {
            $this->updateEnvironmentValue('GOOGLE_DRIVE_ROOT_FOLDER_ID', $validated['google_drive_root_folder_id']);
            Artisan::call('config:clear');
            Log::info('Google Drive root folder updated successfully', ['folder_id' => $validated['google_drive_root_folder_id']]);
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive root folder', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update configuration values.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    protected function updateConfig(string $key, $value): void
    {
        $path = config_path('cloud-storage.php');
        $config = include $path;

        // Update nested array using dot notation
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$config;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current[$lastKey] = $value;

        // Write back to file
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($path, $content);

        // Clear config cache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path);
        }
    }

    // Add method to update .env file with key and value
    private function updateEnvironmentValue(string $key, string $value): void
    {
        $path = base_path('.env');
        if (File::exists($path)) {
            $content = File::get($path);
            $escapedValue = '"' . str_replace('"', '\\"', $value) . '"';
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$escapedValue}",
                    $content
                );
            } else {
                $content .= PHP_EOL . "{$key}={$escapedValue}";
            }
            File::put($path, $content);
        }
    }

    /**
     * Handle the OAuth callback for Google Drive.
     */
    public function callback(Request $request)
    {
        if ($request->has('code')) {
            try {
                $this->driveService->handleCallback(Auth::user(), $request->input('code'));
                return redirect()->route('admin.cloud-storage.index')
                    ->with('success', __('messages.google_drive_connected'));
            } catch (\Exception $e) {
                Log::error('Failed to handle Google Drive OAuth callback', ['error' => $e->getMessage()]);
                return redirect()->back()->with('error', __('messages.settings_update_failed'));
            }
        }
        return redirect()->back()->with('error', __('messages.settings_update_failed'));
    }

    /**
     * Disconnect Google Drive for the current user.
     */
    public function disconnect()
    {
        try {
            $this->driveService->disconnect(Auth::user());
            return redirect()->route('admin.cloud-storage.index')
                ->with('success', __('messages.google_drive_disconnected'));
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Drive', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }
}
