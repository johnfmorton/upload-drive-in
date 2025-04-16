<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class AdminSettingsController extends Controller
{
    /**
     * Show the form for editing the application settings.
     *
     * @return \Illuminate\View\View
     */
    public function edit(): View
    {
        // Directly use the configured branding color. Default to a fallback if not set.
        $branding_color = config('app.branding_color', '#0000FF'); // Default to blue if not set
        Log::info('Reading branding color from config', ['branding_color' => $branding_color]);

        $settings = [
            'company_name' => config('app.company_name', config('app.name')),
            'branding_color' => $branding_color,
            'has_icon' => File::exists(public_path('images/app-icon.png'))
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    /**
     * Update the application settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            // Validate incoming color as a string (e.g., hex, rgb, named color)
            'branding_color' => ['required', 'string', 'max:50'], // Allow various CSS color formats
            'app_icon' => ['nullable', 'image', 'mimes:jpeg,png,svg', 'max:2048'], // 2MB max
        ]);

        try {
            // Update company name in .env
            $this->updateEnvironmentValue('COMPANY_NAME', $validated['company_name']);

            // Update branding color in .env directly with the validated user input
            $this->updateEnvironmentValue('APP_BRANDING_COLOR', $validated['branding_color']);
            Log::info('Updating branding color in .env', ['branding_color' => $validated['branding_color']]);

            // Handle icon upload if provided
            if ($request->hasFile('app_icon')) {
                $icon = $request->file('app_icon');

                // Create images directory if it doesn't exist
                if (!File::exists(public_path('images'))) {
                    File::makeDirectory(public_path('images'), 0755, true);
                }

                // Remove old icon if exists
                if (File::exists(public_path('images/app-icon.png'))) {
                    File::delete(public_path('images/app-icon.png'));
                }

                // Store new icon
                $icon->move(public_path('images'), 'app-icon.png');
            }

            // Clear config cache to reflect changes
            \Artisan::call('config:clear');

            return back()->with('status', 'settings-updated');
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a value in the .env file.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    private function updateEnvironmentValue(string $key, string $value): void
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);

            // Ensure value is wrapped in quotes for .env
            $value = '"' . str_replace('"', '\\"', $value) . '"';

            // If key exists, replace its value
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $content
                );
            } else {
                // If key doesn't exist, append it
                $content .= PHP_EOL . "{$key}={$value}";
            }

            File::put($path, $content);
        }
    }

    /**
     * Remove the uploaded application icon.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyIcon(): RedirectResponse
    {
        $iconPath = public_path('images/app-icon.png');

        try {
            if (File::exists($iconPath)) {
                File::delete($iconPath);
                Log::info('Application icon removed successfully.');
                return back()->with('status', 'icon-removed');
            }
            Log::warning('Attempted to remove non-existent application icon.');
            return back()->withErrors(['error' => 'No icon found to remove.']);
        } catch (\Exception $e) {
            Log::error('Error removing application icon', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Failed to remove icon: ' . $e->getMessage()]);
        }
    }
}
