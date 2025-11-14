<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SessionConfigHelper
{
    /**
     * Validate session configuration and return any warnings
     *
     * @return array Array of warning messages
     */
    public static function validateSessionConfiguration(): array
    {
        $warnings = [];
        
        $appUrl = config('app.url', '');
        $isHttps = self::isHttpsUrl($appUrl);
        $secureCookie = config('session.secure');
        $sameSite = config('session.same_site');
        $httpOnly = config('session.http_only');
        
        // Warn if using HTTPS without secure cookies enabled
        if ($isHttps && !$secureCookie) {
            $warnings[] = 'APP_URL uses HTTPS but SESSION_SECURE_COOKIE is not enabled. This may cause session and CSRF token issues. Set SESSION_SECURE_COOKIE=true in your .env file.';
        }
        
        // Warn if secure cookies are enabled without HTTPS
        if (!$isHttps && $secureCookie) {
            $warnings[] = 'SESSION_SECURE_COOKIE is enabled but APP_URL does not use HTTPS. Cookies will not be sent by browsers. Set SESSION_SECURE_COOKIE=false or update APP_URL to use HTTPS.';
        }
        
        // Warn if SameSite is 'none' without secure cookies
        if ($sameSite === 'none' && !$secureCookie) {
            $warnings[] = 'SESSION_SAME_SITE is set to "none" but SESSION_SECURE_COOKIE is not enabled. Browsers require secure cookies when SameSite=none.';
        }
        
        // Warn if HttpOnly is disabled (security risk)
        if (!$httpOnly) {
            $warnings[] = 'SESSION_HTTP_ONLY is disabled. This exposes session cookies to JavaScript and increases XSS vulnerability. Consider enabling SESSION_HTTP_ONLY=true.';
        }
        
        return $warnings;
    }
    
    /**
     * Log session configuration details
     *
     * @return void
     */
    public static function logSessionConfiguration(): void
    {
        if (!config('app.debug')) {
            return;
        }
        
        $appUrl = config('app.url', '');
        $isHttps = self::isHttpsUrl($appUrl);
        $explicitSecureCookie = env('SESSION_SECURE_COOKIE');
        
        Log::debug('Session Configuration', [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'http_only' => config('session.http_only'),
            'partitioned' => config('session.partitioned'),
            'app_url' => $appUrl,
            'detected_https' => $isHttps,
            'explicit_secure_cookie' => $explicitSecureCookie,
            'auto_detected' => $explicitSecureCookie === null,
        ]);
    }
    
    /**
     * Check if a URL uses HTTPS
     *
     * @param string $url
     * @return bool
     */
    public static function isHttpsUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        return str_starts_with(strtolower(trim($url)), 'https://');
    }
    
    /**
     * Get recommended session configuration based on APP_URL
     *
     * @return array
     */
    public static function getRecommendedConfiguration(): array
    {
        $appUrl = config('app.url', '');
        $isHttps = self::isHttpsUrl($appUrl);
        
        return [
            'SESSION_SECURE_COOKIE' => $isHttps ? 'true' : 'false',
            'SESSION_SAME_SITE' => 'lax',
            'SESSION_HTTP_ONLY' => 'true',
            'SESSION_PARTITIONED_COOKIE' => 'false',
        ];
    }
    
    /**
     * Check if session configuration should use secure cookies
     *
     * @return bool
     */
    public static function shouldUseSecureCookies(): bool
    {
        // Check explicit configuration first
        $explicit = env('SESSION_SECURE_COOKIE');
        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Auto-detect from APP_URL
        $appUrl = env('APP_URL', '');
        return self::isHttpsUrl($appUrl);
    }
}
