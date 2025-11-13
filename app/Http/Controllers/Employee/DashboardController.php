<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Show the employee dashboard.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Get files uploaded to this employee (where company_user_id matches this employee)
        // or files uploaded directly by this employee
        $files = FileUpload::where(function($query) use ($user) {
            $query->where('company_user_id', $user->id)
                  ->orWhere('uploaded_by_user_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(config('file-manager.pagination.items_per_page'));

        // Get storage provider context
        $storageProvider = $this->getStorageProviderContext();

        return view('employee.dashboard', compact('user', 'files', 'storageProvider'));
    }

    /**
     * Get the active cloud storage provider configuration
     *
     * @return array Provider context including name, auth type, and capabilities
     */
    private function getStorageProviderContext(): array
    {
        try {
            $defaultProvider = config('cloud-storage.default');
            
            if (!$defaultProvider) {
                Log::warning('No default cloud storage provider configured');
                return $this->getDefaultProviderContext();
            }
            
            $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
            
            if (!$providerConfig) {
                Log::error("Provider configuration not found: {$defaultProvider}");
                return $this->getDefaultProviderContext();
            }
            
            return [
                'provider' => $defaultProvider,
                'auth_type' => $providerConfig['auth_type'] ?? 'oauth',
                'storage_model' => $providerConfig['storage_model'] ?? 'hierarchical',
                'requires_user_auth' => ($providerConfig['auth_type'] ?? 'oauth') === 'oauth',
                'display_name' => $this->getProviderDisplayName($defaultProvider),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting storage provider context', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->getDefaultProviderContext();
        }
    }

    /**
     * Get human-readable provider name
     *
     * @param  string  $provider  Provider identifier
     * @return string Display name
     */
    private function getProviderDisplayName(string $provider): string
    {
        return match($provider) {
            'google-drive' => 'Google Drive',
            'amazon-s3' => 'Amazon S3',
            'microsoft-teams' => 'Microsoft Teams',
            default => ucwords(str_replace('-', ' ', $provider)),
        };
    }

    /**
     * Get default provider context for error fallback
     *
     * @return array Default provider context with error flag
     */
    private function getDefaultProviderContext(): array
    {
        return [
            'provider' => 'unknown',
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
            'requires_user_auth' => true,
            'display_name' => 'Cloud Storage',
            'error' => true,
        ];
    }
}
