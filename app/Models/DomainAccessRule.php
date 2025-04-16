<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DomainAccessRule extends Model
{
    protected $fillable = [
        'mode',
        'rules',
        'allow_public_registration',
    ];

    protected $casts = [
        'rules' => 'array',
        'allow_public_registration' => 'boolean',
    ];

    /**
     * Check if an email domain is allowed based on the current rules
     */
    public function isEmailAllowed(string $email): bool
    {
        Log::info('Checking email access', [
            'email' => $email,
            'mode' => $this->mode,
            'rules' => $this->rules
        ]);

        $domain = substr(strrchr($email, "@"), 1);
        if (!$domain) {
            Log::warning('Invalid email format - no domain found', ['email' => $email]);
            return false;
        }

        $rules = $this->rules ?? [];
        $isMatch = false;

        foreach ($rules as $rule) {
            // If rule contains @, extract the domain part
            $ruleDomain = strpos($rule, '@') !== false ? substr(strrchr($rule, "@"), 1) : $rule;

            // Handle wildcard domains specially
            if (str_starts_with($ruleDomain, '*.')) {
                // Convert *.example.com to (subdomain\.)?example\.com
                $baseDomain = substr($ruleDomain, 2); // Remove *. prefix
                $pattern = '/^(?:[a-zA-Z0-9-]+\.)?'. preg_quote($baseDomain, '/') . '$/i';
            } else {
                // Normal domain matching
                $pattern = '/^' . preg_quote($ruleDomain, '/') . '$/i';
            }

            Log::debug('Checking rule', [
                'rule' => $rule,
                'ruleDomain' => $ruleDomain,
                'pattern' => $pattern,
                'domain' => $domain,
                'email' => $email
            ]);

            // Check against the domain
            if (preg_match($pattern, $domain)) {
                $isMatch = true;
                Log::info('Rule matched', [
                    'rule' => $rule,
                    'email' => $email,
                    'domain' => $domain,
                    'pattern' => $pattern
                ]);
                break;
            }
        }

        $isAllowed = $this->mode === 'whitelist' ? $isMatch : !$isMatch;
        Log::info('Access decision', [
            'email' => $email,
            'isAllowed' => $isAllowed,
            'mode' => $this->mode,
            'matchedRule' => $isMatch
        ]);

        return $isAllowed;
    }

    /**
     * Get the validation rules for domain rules
     */
    public static function getDomainRuleValidation(): array
    {
        return [
            'mode' => ['required', 'string', 'in:blacklist,whitelist'],
            'rules' => ['nullable', 'array'],
            'rules.*' => ['string', 'regex:/^(?:\*\.)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$|^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i'],
            'allow_public_registration' => ['required', 'boolean'],
        ];
    }
}
