<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CloudStorageController extends Controller
{
    /**
     * Display the cloud storage configuration page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.cloud-storage');
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
            $config = Config::get('cloud-storage.providers.microsoft-teams');
            $config['client_id'] = $validated['microsoft_teams_client_id'];
            $config['root_folder_id'] = $validated['microsoft_teams_root_folder_id'];

            if (!empty($validated['microsoft_teams_client_secret'])) {
                $config['client_secret'] = $validated['microsoft_teams_client_secret'];
            }

            $this->updateConfig('cloud-storage.providers.microsoft-teams', $config);

            Log::info('Microsoft Teams configuration updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update Microsoft Teams configuration', [
                'error' => $e->getMessage()
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
            $config = Config::get('cloud-storage.providers.dropbox');
            $config['client_id'] = $validated['dropbox_client_id'];
            $config['root_folder'] = $validated['dropbox_root_folder'];

            if (!empty($validated['dropbox_client_secret'])) {
                $config['client_secret'] = $validated['dropbox_client_secret'];
            }

            $this->updateConfig('cloud-storage.providers.dropbox', $config);

            Log::info('Dropbox configuration updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update Dropbox configuration', [
                'error' => $e->getMessage()
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
            $config = Config::get('services.google_drive');
            $config['client_id'] = $validated['google_drive_client_id'];
            $config['root_folder_id'] = $validated['google_drive_root_folder_id'];

            if (!empty($validated['google_drive_client_secret'])) {
                $config['client_secret'] = $validated['google_drive_client_secret'];
            }

            $this->updateConfig('services.google_drive', $config);

            Log::info('Google Drive configuration updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive configuration', [
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
            $this->updateConfig('cloud-storage.default', $validated['default_provider']);

            Log::info('Default storage provider updated successfully', [
                'provider' => $validated['default_provider']
            ]);
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update default storage provider', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
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
}
