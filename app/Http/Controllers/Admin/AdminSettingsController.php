<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Spatie\Color\Color;
use Spatie\Color\Exceptions\InvalidColorValue;
use Spatie\Color\Hex;
use Spatie\Color\Rgb;
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
        $branding_color_hex = '#000000'; // Default hex
        $oklch_string = config('app.branding_color');
        Log::info('Reading branding color from config', ['oklch_string' => $oklch_string]);

        if ($oklch_string) {
            try {
                Log::info('Attempting to parse OKLCH string.');
                // Parse OKLCH string using regex
                if (preg_match('/oklch\((\d+)%\s+([\d.]+)\s+([\d.]+)\s*\/\s*([\d.]+)\)/', $oklch_string, $matches)) {
                    Log::info('OKLCH regex matched', ['matches' => $matches]);
                    $l = floatval($matches[1]) / 100; // Convert percentage to 0-1
                    $c = floatval($matches[2]);
                    $h = floatval($matches[3]);
                    $alpha = floatval($matches[4]);
                    Log::info('Parsed LCH values', ['L' => $l, 'C' => $c, 'H' => $h, 'alpha' => $alpha]);

                    // Convert OKLCH to RGB (simplified approximation)
                    $s = $c * 2;
                    $c_hsl = (1 - abs(2 * $l - 1)) * $s;
                    $x = $c_hsl * (1 - abs(fmod($h / 60, 2) - 1));
                    $m = $l - $c_hsl/2;
                    Log::info('Intermediate HSL values', ['s' => $s, 'c_hsl' => $c_hsl, 'x' => $x, 'm' => $m]);

                    if ($h >= 0 && $h < 60) {
                        $r_prime = $c_hsl; $g_prime = $x; $b_prime = 0;
                    } else if ($h >= 60 && $h < 120) {
                        $r_prime = $x; $g_prime = $c_hsl; $b_prime = 0;
                    } else if ($h >= 120 && $h < 180) {
                        $r_prime = 0; $g_prime = $c_hsl; $b_prime = $x;
                    } else if ($h >= 180 && $h < 240) {
                        $r_prime = 0; $g_prime = $x; $b_prime = $c_hsl;
                    } else if ($h >= 240 && $h < 300) {
                        $r_prime = $x; $g_prime = 0; $b_prime = $c_hsl;
                    } else {
                        $r_prime = $c_hsl; $g_prime = 0; $b_prime = $x;
                    }
                    Log::info('Intermediate RGB primes', ['r_prime' => $r_prime, 'g_prime' => $g_prime, 'b_prime' => $b_prime]);

                    // Convert to RGB values
                    $r = round(($r_prime + $m) * 255);
                    $g = round(($g_prime + $m) * 255);
                    $b = round(($b_prime + $m) * 255);
                    Log::info('Final RGB values', ['R' => $r, 'G' => $g, 'B' => $b]);

                    // Ensure RGB values are within valid range before creating the color object
                    $r = max(0, min(255, $r));
                    $g = max(0, min(255, $g));
                    $b = max(0, min(255, $b));
                    Log::info('Clamped RGB values', ['R' => $r, 'G' => $g, 'B' => $b]);

                    // Create RGB color and convert to Hex
                    $rgb = Rgb::fromString("rgb($r,$g,$b)");
                    $branding_color_hex = $rgb->toHex()->__toString();
                    Log::info('Successfully converted to Hex', ['hex' => $branding_color_hex]);
                } else {
                    Log::warning('OKLCH regex did not match the string.', ['string' => $oklch_string]);
                }
            } catch (\Exception $e) {
                Log::error('Error during OKLCH to Hex conversion', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        } else {
            Log::info('No branding color found in config, using default.');
        }

        $settings = [
            'company_name' => config('app.company_name', config('app.name')),
            'branding_color' => $branding_color_hex,
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
            // Validate incoming color as Hex
            'branding_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'app_icon' => ['nullable', 'image', 'mimes:jpeg,png,svg', 'max:2048'], // 2MB max
        ]);

        try {
            // Update company name in .env
            $this->updateEnvironmentValue('COMPANY_NAME', $validated['company_name']);

            // Convert validated Hex color to OKLCH via RGB
            try {
                $hexColor = Hex::fromString($validated['branding_color']);
                $rgb = $hexColor->toRgb();

                // Convert RGB values to relative values (0-1)
                $r = $rgb->red() / 255;
                $g = $rgb->green() / 255;
                $b = $rgb->blue() / 255;

                // Calculate lightness (simplified approximation)
                $l = 0.5 * ($r + $g + $b);

                // Calculate chroma (simplified approximation)
                $c = max($r, $g, $b) - min($r, $g, $b);

                // Calculate hue (simplified approximation)
                $h = 0;
                if ($c > 0) {
                    if (max($r, $g, $b) === $r) {
                        $h = 60 * fmod((($g - $b) / $c), 6);
                    } else if (max($r, $g, $b) === $g) {
                        $h = 60 * (($b - $r) / $c + 2);
                    } else {
                        $h = 60 * (($r - $g) / $c + 4);
                    }
                }
                if ($h < 0) $h += 360;

                // Format as OKLCH string
                $oklch_string = sprintf('oklch(%d%% %.1f %.1f / 1.0)', round($l * 100), $c, $h);
            } catch (InvalidColorValue $e) {
                // Log error if conversion fails, keep default
                report($e);
                $oklch_string = 'oklch(50% 0.2 220 / 1.0)'; // Default fallback
            }

            // Update branding color in .env with OKLCH string
            $this->updateEnvironmentValue('APP_BRANDING_COLOR', $oklch_string);

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
