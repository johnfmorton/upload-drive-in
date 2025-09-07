# Domain Rules Cache Service Translations

This document describes the internationalization (i18n) implementation for the Domain Rules Cache Service.

## Overview

The `DomainRulesCacheService` has been enhanced with multi-language support for user-facing messages and admin interface elements. This ensures that cache statistics, error messages, and command outputs are properly localized.

## Supported Languages

- **English (en)** - Default language
- **Spanish (es)** - Full translation support
- **French (fr)** - Full translation support

## Translation Keys

### Core Service Messages

| Key | English | Spanish | French |
|-----|---------|---------|--------|
| `domain_rules_cache_failed` | Failed to retrieve domain access rules from cache | Error al recuperar las reglas de acceso de dominio desde la caché | Échec de la récupération des règles d'accès de domaine depuis le cache |
| `domain_rules_cache_cleared` | Domain access rules cache has been cleared | La caché de reglas de acceso de dominio ha sido limpiada | Le cache des règles d'accès de domaine a été vidé |
| `domain_rules_cache_warmed` | Domain access rules cache has been warmed up | La caché de reglas de acceso de dominio ha sido precargada | Le cache des règles d'accès de domaine a été préchauffé |
| `domain_rules_not_configured` | No domain access rules configured - using default settings | No hay reglas de acceso de dominio configuradas - usando configuración predeterminada | Aucune règle d'accès de domaine configurée - utilisation des paramètres par défaut |

### Cache Statistics Labels

| Key | English | Spanish | French |
|-----|---------|---------|--------|
| `cache_hit` | Cache Hit | Acierto de Caché | Succès de Cache |
| `cache_miss` | Cache Miss | Fallo de Caché | Échec de Cache |
| `cache_key` | Cache Key | Clave de Caché | Clé de Cache |
| `cache_ttl` | Cache TTL (seconds) | TTL de Caché (segundos) | TTL de Cache (secondes) |
| `rules_loaded` | Rules Loaded | Reglas Cargadas | Règles Chargées |
| `rules_mode` | Rules Mode | Modo de Reglas | Mode des Règles |
| `rules_count` | Number of Rules | Número de Reglas | Nombre de Règles |

### Command Interface Labels

| Key | English | Spanish | French |
|-----|---------|---------|--------|
| `domain_rules_cache_command_yes` | Yes | Sí | Oui |
| `domain_rules_cache_command_no` | No | No | Non |
| `domain_rules_cache_command_property` | Property | Propiedad | Propriété |
| `domain_rules_cache_command_value` | Value | Valor | Valeur |
| `domain_rules_cache_command_seconds` | seconds | segundos | secondes |

## Usage Examples

### Service Methods

```php
// Get user-friendly cache statistics (automatically localized)
$cacheService = app(DomainRulesCacheService::class);
$stats = $cacheService->getUserFriendlyCacheStats();

// Clear cache with localized logging
$cacheService->clearCache(); // Logs in current locale

// Warm cache with localized logging
$cacheService->warmCache(); // Logs in current locale
```

### Artisan Command

```bash
# Display cache statistics in current locale
php artisan domain-rules:cache stats

# Clear cache with localized confirmation
php artisan domain-rules:cache clear

# Warm cache with localized confirmation
php artisan domain-rules:cache warm

# Get JSON output (language-independent)
php artisan domain-rules:cache stats --format=json
```

### Programmatic Locale Switching

```php
use Illuminate\Support\Facades\App;

// Switch to Spanish
App::setLocale('es');
$spanishStats = $cacheService->getUserFriendlyCacheStats();

// Switch to French
App::setLocale('fr');
$frenchStats = $cacheService->getUserFriendlyCacheStats();

// Reset to English
App::setLocale('en');
$englishStats = $cacheService->getUserFriendlyCacheStats();
```

## Implementation Details

### Service Layer Translation

The `DomainRulesCacheService` uses Laravel's `__()` helper function to translate messages:

```php
Log::error(__('messages.domain_rules_cache_failed'), [
    'error' => $e->getMessage(),
    // ... additional context
]);
```

### User-Friendly Statistics

The `getUserFriendlyCacheStats()` method returns an array with translated keys and values:

```php
public function getUserFriendlyCacheStats(): array
{
    $stats = $this->getCacheStats();
    
    return [
        __('messages.cache_key') => $stats['cache_key'],
        __('messages.cache_ttl') => $stats['cache_ttl'] . ' ' . __('messages.domain_rules_cache_command_seconds'),
        __('messages.cache_hit') => $stats['cache_hit'] ? __('messages.domain_rules_cache_command_yes') : __('messages.domain_rules_cache_command_no'),
        // ... more translated entries
    ];
}
```

### Command Interface

The `DomainRulesCacheCommand` uses translations for all user-facing output:

```php
$this->table([
    __('messages.domain_rules_cache_command_property'), 
    __('messages.domain_rules_cache_command_value')
], $tableData);
```

## Testing

Translation functionality is tested in `DomainRulesCacheServiceTranslationTest`:

- Verifies correct translations in all supported languages
- Tests locale switching behavior
- Ensures translated keys and values are properly formatted

## Adding New Languages

To add support for a new language:

1. Create a new language file: `resources/lang/{locale}/messages.php`
2. Copy the translation keys from an existing language file
3. Translate all the domain rules cache service keys
4. Add test cases for the new language in the translation test
5. Update this documentation

## File Locations

- **Service**: `app/Services/DomainRulesCacheService.php`
- **Command**: `app/Console/Commands/DomainRulesCacheCommand.php`
- **English Translations**: `resources/lang/en/messages.php`
- **Spanish Translations**: `resources/lang/es/messages.php`
- **French Translations**: `resources/lang/fr/messages.php`
- **Tests**: `tests/Unit/Services/DomainRulesCacheServiceTranslationTest.php`

## Best Practices

1. **Always use translation keys** for user-facing messages
2. **Keep debug/internal logging** in English for consistency
3. **Test translations** in all supported languages
4. **Use descriptive translation keys** that indicate their purpose
5. **Group related translations** with consistent prefixes
6. **Provide context** in translation keys when needed

## Future Enhancements

- Add support for additional languages as needed
- Implement pluralization for count-based messages
- Add date/time localization for cache timestamps
- Consider adding RTL language support if needed