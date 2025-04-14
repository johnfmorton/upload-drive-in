<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;

class AdminSettingsController extends Controller
{
    /**
     * Show the form for editing the application settings.
     *
     * @return \Illuminate\View\View
     */
    public function edit(): View
    {
        $settings = [
            'business_name' => config('app.name'),
            'branding_color' => config('app.branding_color', '#000000'),
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
            'business_name' => ['required', 'string', 'max:255'],
            'branding_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'app_icon' => ['nullable', 'image', 'mimes:jpeg,png,svg', 'max:2048'], // 2MB max
        ]);

        try {
            // Update business name in .env
            $this->updateEnvironmentValue('APP_NAME', $validated['business_name']);

            // Update branding color in .env
            $this->updateEnvironmentValue('APP_BRANDING_COLOR', $validated['branding_color']);

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

            // Escape special characters in the value
            $value = str_replace('"', '\"', $value);

            // If key exists, replace its value
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}=\"{$value}\"",
                    $content
                );
            } else {
                // If key doesn't exist, append it
                $content .= PHP_EOL . "{$key}=\"{$value}\"";
            }

            File::put($path, $content);
        }
    }
}
