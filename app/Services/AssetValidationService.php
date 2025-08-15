<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class AssetValidationService
{
    private array $assetPaths;
    private array $nodeChecks;

    public function __construct()
    {
        $this->assetPaths = Config::get('setup.asset_paths', [
            'vite_manifest' => 'public/build/manifest.json',
            'build_directory' => 'public/build',
            'package_json' => 'package.json',
        ]);

        $this->nodeChecks = Config::get('setup.asset_checks', [
            'vite_manifest_required' => true,
            'node_environment_check' => true,
            'build_instructions_enabled' => true,
        ]);
    }

    /**
     * Determine if the Vite dev server is active by checking the hot file
     */
    public function isViteHotActive(): bool
    {
        return file_exists(public_path('hot'));
    }

    /**
     * Check if the Vite manifest file exists
     */
    public function validateViteManifest(): bool
    {
        if (!($this->nodeChecks['vite_manifest_required'] ?? true)) {
            return true;
        }

        $manifestPath = $this->getManifestPath();
        return File::exists($manifestPath) && $this->isValidManifestFile($manifestPath);
    }

    /**
     * Get the full path to the Vite manifest file
     */
    public function getManifestPath(): string
    {
        return base_path($this->assetPaths['vite_manifest']);
    }

    /**
     * Get the full path to the build directory
     */
    public function getBuildDirectoryPath(): string
    {
        return base_path($this->assetPaths['build_directory']);
    }

    /**
     * Check if the build directory exists and contains files
     */
    public function validateBuildDirectory(): bool
    {
        $buildPath = $this->getBuildDirectoryPath();

        if (!File::exists($buildPath) || !File::isDirectory($buildPath)) {
            return false;
        }

        // Check if directory has any files (not just empty)
        $files = File::files($buildPath);
        return count($files) > 0;
    }

    /**
     * Get build instructions for the user
     */
    public function getBuildInstructions(): array
    {
        return [
            'title' => 'Build Frontend Assets',
            'description' => 'Your application needs to compile frontend assets before it can run properly.',
            'steps' => [
                [
                    'title' => 'Install Dependencies',
                    'command' => 'npm ci',
                    'description' => 'Install all required Node.js dependencies',
                ],
                [
                    'title' => 'Build Assets',
                    'command' => 'npm run build',
                    'description' => 'Compile and optimize frontend assets for production',
                ],
            ],
            'troubleshooting' => [
                'Node.js not installed' => 'Visit https://nodejs.org to download and install Node.js',
                'Permission errors' => 'Try running commands with sudo (Linux/Mac) or as Administrator (Windows)',
                'Build fails' => 'Delete node_modules folder and package-lock.json, then run npm ci again',
                'Out of memory' => 'Try running: NODE_OPTIONS="--max-old-space-size=4096" npm run build',
            ],
        ];
    }

    /**
     * Check Node.js environment and dependencies
     */
    public function checkNodeEnvironment(): array
    {
        $checks = [];

        // Check if package.json exists
        $packageJsonPath = base_path($this->assetPaths['package_json']);
        $checks['package_json_exists'] = File::exists($packageJsonPath);

        // Check if node_modules exists
        $nodeModulesPath = base_path('node_modules');
        $checks['node_modules_exists'] = File::exists($nodeModulesPath) && File::isDirectory($nodeModulesPath);

        // Check if package-lock.json exists
        $packageLockPath = base_path('package-lock.json');
        $checks['package_lock_exists'] = File::exists($packageLockPath);

        // Check if Vite config exists
        $viteConfigPath = base_path('vite.config.js');
        $checks['vite_config_exists'] = File::exists($viteConfigPath);

        return $checks;
    }

    /**
     * Get detailed asset validation results
     */
    public function getAssetValidationResults(): array
    {
        return [
            'vite_manifest_exists' => $this->validateViteManifest(),
            'build_directory_exists' => $this->validateBuildDirectory(),
            'node_environment' => $this->checkNodeEnvironment(),
            'manifest_path' => $this->getManifestPath(),
            'build_directory_path' => $this->getBuildDirectoryPath(),
        ];
    }

    /**
     * Check if all asset requirements are met
     */
    public function areAssetRequirementsMet(): bool
    {
        // Allow development hot-reload when explicitly enabled or in local env
        if ((env('DEV_MODE', false) || app()->environment('local')) && $this->isViteHotActive()) {
            return true;
        }

        if (!($this->nodeChecks['vite_manifest_required'] ?? true)) {
            return true;
        }

        return $this->validateViteManifest() && $this->validateBuildDirectory();
    }

    /**
     * Get asset configuration paths
     */
    public function getAssetPaths(): array
    {
        return $this->assetPaths;
    }

    /**
     * Get asset check configuration
     */
    public function getAssetChecks(): array
    {
        return $this->nodeChecks;
    }

    /**
     * Validate that the manifest file contains valid JSON and expected structure
     */
    private function isValidManifestFile(string $manifestPath): bool
    {
        try {
            $content = File::get($manifestPath);
            $manifest = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Basic validation - manifest should be an array/object
            return is_array($manifest);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get missing asset requirements with user-friendly messages
     */
    public function getMissingAssetRequirements(): array
    {
        $missing = [];
        $results = $this->getAssetValidationResults();

        if (!$results['vite_manifest_exists']) {
            $missing[] = [
                'type' => 'vite_manifest',
                'message' => 'Vite manifest file is missing',
                'path' => $results['manifest_path'],
                'solution' => 'Run "npm run build" to generate the manifest file',
            ];
        }

        if (!$results['build_directory_exists']) {
            $missing[] = [
                'type' => 'build_directory',
                'message' => 'Build directory is missing or empty',
                'path' => $results['build_directory_path'],
                'solution' => 'Run "npm run build" to generate build assets',
            ];
        }

        $nodeEnv = $results['node_environment'];
        if (!$nodeEnv['package_json_exists']) {
            $missing[] = [
                'type' => 'package_json',
                'message' => 'package.json file is missing',
                'path' => base_path('package.json'),
                'solution' => 'Ensure you have the complete project files including package.json',
            ];
        }

        if (!$nodeEnv['node_modules_exists']) {
            $missing[] = [
                'type' => 'node_modules',
                'message' => 'Node.js dependencies are not installed',
                'path' => base_path('node_modules'),
                'solution' => 'Run "npm ci" to install dependencies',
            ];
        }

        return $missing;
    }

    /**
     * Get asset build status for real-time checking
     */
    public function getAssetBuildStatus(): array
    {
        $results = $this->getAssetValidationResults();
        $missing = $this->getMissingAssetRequirements();

        return [
            'ready' => $this->areAssetRequirementsMet(),
            'checks' => $results,
            'missing' => $missing,
            'next_step' => empty($missing) ? 'database' : 'assets',
        ];
    }
}
