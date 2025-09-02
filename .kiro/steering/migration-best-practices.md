# Laravel Migration Best Practices

This document establishes migration patterns and best practices for this Laravel project to prevent data inconsistency issues.

## Core Principles

### 1. Never Set Arbitrary Defaults for Calculated Fields
When adding fields that should be calculated based on existing data, NEVER set a blanket default that ignores actual state.

### 2. Three-Step Migration Pattern for Derived Fields
1. **Add field as nullable** initially
2. **Calculate correct values** for existing records
3. **Make non-nullable** with appropriate default for new records

### 3. Always Consider Existing Data
Every migration that adds calculated/derived fields must include logic to properly populate existing records.

## Migration Patterns

### ❌ WRONG: Arbitrary Default Pattern
```php
public function up(): void
{
    Schema::table('table_name', function (Blueprint $table) {
        // This sets ALL existing records to 'default_value' regardless of actual state
        $table->enum('calculated_field', ['option1', 'option2'])->default('default_value');
    });
}
```

### ✅ CORRECT: Calculate-Then-Default Pattern
```php
public function up(): void
{
    // Step 1: Add field as nullable first
    Schema::table('table_name', function (Blueprint $table) {
        $table->enum('calculated_field', ['option1', 'option2'])->nullable();
    });

    // Step 2: Calculate correct values for existing records
    $this->calculateFieldForExistingRecords();

    // Step 3: Make field non-nullable with default for new records
    Schema::table('table_name', function (Blueprint $table) {
        $table->enum('calculated_field', ['option1', 'option2'])
              ->default('safe_default')
              ->change();
    });
}

private function calculateFieldForExistingRecords(): void
{
    $records = Model::whereNull('calculated_field')->get();
    
    foreach ($records as $record) {
        try {
            $calculatedValue = $this->calculateValue($record);
            $record->update(['calculated_field' => $calculatedValue]);
        } catch (\Exception $e) {
            Log::warning('Migration calculation failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);
            // Fallback to safe default
            $record->update(['calculated_field' => 'safe_default']);
        }
    }
}
```

## Required Migration Types

### Status/Enum Fields
When adding status fields that should reflect current state:
- ✅ Calculate based on existing data and business logic
- ❌ Set all records to same arbitrary value

### Derived/Calculated Fields
When adding fields that derive from other fields:
- ✅ Calculate from source fields using business logic
- ❌ Leave empty or set to arbitrary default

### Foreign Key Additions
When adding relationships:
- ✅ Populate based on existing relationships or business rules
- ❌ Leave as NULL when relationships should exist

## Migration Checklist

Before creating any migration, verify:

- [ ] Does this field derive its value from existing data?
- [ ] Will setting a default value create inconsistent state?
- [ ] Do I need to calculate values for existing records?
- [ ] Have I included proper error handling in calculations?
- [ ] Does the migration log its progress and any issues?
- [ ] Is there a safe rollback strategy?

## Error Handling in Migrations

Always include proper error handling:

```php
private function calculateFieldForExistingRecords(): void
{
    $records = Model::whereNull('calculated_field')->get();
    $processed = 0;
    $errors = 0;
    
    Log::info("Starting field calculation for {$records->count()} records");
    
    foreach ($records as $record) {
        try {
            $calculatedValue = $this->calculateValue($record);
            $record->update(['calculated_field' => $calculatedValue]);
            $processed++;
        } catch (\Exception $e) {
            $errors++;
            Log::error('Migration calculation failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);
            // Use safe fallback
            $record->update(['calculated_field' => 'safe_default']);
        }
    }
    
    Log::info("Field calculation completed", [
        'total_records' => $records->count(),
        'processed' => $processed,
        'errors' => $errors
    ]);
}
```

## Real Example: Cloud Storage Status Issue

The cloud storage consolidated status issue was caused by this anti-pattern:

```php
// ❌ What caused the production issue
Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
    $table->enum('consolidated_status', [
        'healthy', 'authentication_required', 'connection_issues', 'not_connected'
    ])->default('not_connected')->after('status');  // Set ALL to 'not_connected'
});
```

This made ALL existing records show "not_connected" even when they were actually healthy and connected.

### The Correct Implementation
```php
// ✅ How it should have been done
public function up(): void
{
    // Step 1: Add nullable field
    Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
        $table->enum('consolidated_status', [
            'healthy', 'authentication_required', 'connection_issues', 'not_connected'
        ])->nullable()->after('status');
    });

    // Step 2: Calculate correct values
    $this->calculateConsolidatedStatusForExistingRecords();

    // Step 3: Make non-nullable with default
    Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
        $table->enum('consolidated_status', [
            'healthy', 'authentication_required', 'connection_issues', 'not_connected'
        ])->default('not_connected')->change();
    });
}

private function calculateConsolidatedStatusForExistingRecords(): void
{
    $healthService = app(CloudStorageHealthService::class);
    $records = CloudStorageHealthStatus::whereNull('consolidated_status')->get();
    
    foreach ($records as $record) {
        try {
            $user = User::find($record->user_id);
            if ($user) {
                $consolidatedStatus = $healthService->determineConsolidatedStatus($user, $record->provider);
                $record->update(['consolidated_status' => $consolidatedStatus]);
            }
        } catch (\Exception $e) {
            $record->update(['consolidated_status' => 'not_connected']);
        }
    }
}
```

## When to Create Data Fix Migrations

Create a separate data fix migration when:
- An existing migration created inconsistent data
- Business logic changes require recalculating existing values
- Data corruption is discovered that affects multiple records

## Deployment Considerations

### Pre-Migration Checks
- Always backup production data before running migrations
- Test migrations on production-like data locally
- Verify the migration handles edge cases

### Post-Migration Verification
- Include verification logic in migrations when possible
- Create artisan commands for manual verification
- Monitor logs for migration-related issues

## Integration with Existing Systems

### Health Status Auto-Correction
The `CloudStorageHealthService` now includes automatic detection and correction of inconsistent status data to prevent future issues.

### Scheduled Maintenance
A daily scheduled task (`cloud-storage:fix-health-status`) automatically corrects any inconsistencies that might arise.

### Deployment Scripts
The `scripts/post-deployment-fixes.sh` script includes health status fixes as part of the deployment process.

This multi-layered approach ensures that data inconsistencies are caught and corrected automatically, reducing the need for manual intervention.