# Laravel Migration Best Practices

## When Adding Calculated/Derived Fields

When adding fields that should be calculated based on existing data, follow this pattern:

### ❌ Wrong Way (What Caused the Issue)
```php
public function up(): void
{
    Schema::table('table_name', function (Blueprint $table) {
        // This sets ALL existing records to 'default_value' regardless of actual state
        $table->enum('calculated_field', ['option1', 'option2'])->default('default_value');
    });
}
```

### ✅ Correct Way
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
              ->default('default_value')
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
            // Fallback to safe default
            $record->update(['calculated_field' => 'safe_default']);
        }
    }
}
```

## Types of Migrations That Need Data Updates

### 1. Status/Enum Fields
When adding status fields that should reflect current state:
- ✅ Calculate based on existing data
- ❌ Set all to same default value

### 2. Derived/Calculated Fields
When adding fields that derive from other fields:
- ✅ Calculate from source fields
- ❌ Leave empty or set to arbitrary default

### 3. Normalization Changes
When moving data between tables:
- ✅ Migrate existing data properly
- ❌ Leave orphaned or inconsistent data

### 4. Foreign Key Additions
When adding relationships:
- ✅ Populate based on existing relationships
- ❌ Leave as NULL when relationships exist

## Migration Checklist

Before creating a migration that adds calculated fields:

- [ ] Does this field derive its value from existing data?
- [ ] Will setting a default value create inconsistent state?
- [ ] Do I need to calculate values for existing records?
- [ ] Have I tested this on a copy of production data?
- [ ] Does the migration handle edge cases and errors?
- [ ] Is there a safe rollback strategy?

## Testing Migrations

### Local Testing
```bash
# Test with production-like data
php artisan migrate:fresh --seed
php artisan migrate

# Verify data consistency
php artisan tinker
# Check that calculated fields match expected values
```

### Production Deployment
```bash
# Always backup before migrations
mysqldump database > backup.sql

# Run migration
php artisan migrate

# Verify results
php artisan tinker
# Spot check critical records
```

## Common Pitfalls

### 1. Setting Defaults Without Calculation
```php
// ❌ This makes ALL records show 'inactive' even if they're active
$table->enum('status', ['active', 'inactive'])->default('inactive');
```

### 2. Not Handling Existing Relationships
```php
// ❌ This breaks existing relationships
$table->foreignId('category_id')->constrained()->default(1);
```

### 3. Ignoring Data Dependencies
```php
// ❌ This doesn't account for existing business logic
$table->boolean('is_premium')->default(false);
// Should calculate based on existing subscription data
```

## Recovery Strategies

If you discover a migration caused data inconsistency:

### 1. Create a Fix Migration
```php
// Create new migration to fix the data
php artisan make:migration fix_inconsistent_data
```

### 2. Add Automatic Detection
```php
// Add logic to detect and fix inconsistencies in real-time
public function getStatusAttribute($value)
{
    if ($this->detectInconsistency()) {
        $this->fixInconsistency();
        return $this->fresh()->status;
    }
    return $value;
}
```

### 3. Create Maintenance Commands
```php
// Create artisan commands for manual fixes
php artisan make:command FixDataInconsistency
```

## Example: The Cloud Storage Status Fix

The issue we encountered was caused by this migration pattern:

```php
// ❌ What happened (caused the issue)
$table->enum('consolidated_status', [...])
      ->default('not_connected')  // Set ALL records to 'not_connected'
      ->after('status');

// ✅ What should have happened
$table->enum('consolidated_status', [...])->nullable()->after('status');
// Calculate correct values for existing records
$this->calculateConsolidatedStatus();
// Then make non-nullable with default
```

This is why users had to run manual commands to fix their data - the migration created inconsistent state that didn't match the actual connection status.