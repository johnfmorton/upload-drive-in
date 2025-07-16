<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user
        $user = Auth::user();
        
        if (!$user) {
            Log::warning('Unauthenticated user attempted to access file', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication required to access files.',
            ], 401);
        }

        // Get the file from the route parameter
        $file = $this->getFileFromRequest($request);
        
        if (!$file) {
            Log::warning('File not found in request', [
                'user_id' => $user->id,
                'route_parameters' => $request->route()->parameters(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        // Check if the user can access this file
        if (!$file->canBeAccessedBy($user)) {
            Log::warning('User attempted to access unauthorized file', [
                'user_id' => $user->id,
                'user_role' => $user->role->value,
                'file_id' => $file->id,
                'file_client_user_id' => $file->client_user_id,
                'file_uploaded_by_user_id' => $file->uploaded_by_user_id,
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $this->getAccessDeniedMessage($user),
            ], 403);
        }

        // Log successful file access for audit purposes
        Log::info('User accessed file', [
            'user_id' => $user->id,
            'user_role' => $user->role->value,
            'file_id' => $file->id,
            'file_name' => $file->original_filename,
            'action' => $this->getActionFromRequest($request),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Extract the file from the request parameters.
     *
     * @param Request $request
     * @return FileUpload|null
     */
    private function getFileFromRequest(Request $request): ?FileUpload
    {
        // Try to get file from route parameter
        $fileParam = $request->route('file');
        
        if ($fileParam instanceof FileUpload) {
            return $fileParam;
        }
        
        // If it's an ID, try to find the file
        if (is_numeric($fileParam)) {
            return FileUpload::find($fileParam);
        }
        
        // Try to get file ID from request data for bulk operations
        $fileIds = $request->input('file_ids', []);
        if (!empty($fileIds) && is_array($fileIds)) {
            // For bulk operations, we'll validate each file individually
            // This method handles single file access, bulk validation happens in the controller
            return null;
        }
        
        return null;
    }

    /**
     * Get the action being performed from the request.
     *
     * @param Request $request
     * @return string
     */
    private function getActionFromRequest(Request $request): string
    {
        $routeName = $request->route()->getName();
        
        return match(true) {
            str_contains($routeName, 'preview') => 'preview',
            str_contains($routeName, 'download') => 'download',
            str_contains($routeName, 'thumbnail') => 'thumbnail',
            str_contains($routeName, 'delete') => 'delete',
            default => 'access'
        };
    }

    /**
     * Get a role-specific access denied message.
     *
     * @param \App\Models\User $user
     * @return string
     */
    private function getAccessDeniedMessage($user): string
    {
        return match($user->role) {
            \App\Enums\UserRole::ADMIN => 'Access denied. This should not happen for admin users.',
            \App\Enums\UserRole::EMPLOYEE => 'Access denied. You can only access files from clients you manage or files you uploaded.',
            \App\Enums\UserRole::CLIENT => 'Access denied. You can only access files you have uploaded.',
            default => 'Access denied. You do not have permission to access this file.',
        };
    }
}
