<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportSqliteData extends Command
{
    protected $signature = 'db:export-sqlite';
    protected $description = 'Export data from SQLite database to JSON files';

    public function handle()
    {
        // Store current connection
        $currentConnection = config('database.default');

        // Switch to SQLite
        config(['database.default' => 'sqlite']);

        // Get all tables
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT IN ('migrations', 'sqlite_sequence')");

        // Create storage directory if it doesn't exist
        $exportPath = storage_path('app/private/db_export');
        if (!File::exists($exportPath)) {
            File::makeDirectory($exportPath, 0755, true);
        }

        foreach ($tables as $table) {
            $tableName = $table->name;
            $this->info("Exporting table: {$tableName}");

            // Get all data from table
            $data = DB::table($tableName)->get();

            // Save to JSON file
            $jsonPath = $exportPath . "/{$tableName}.json";
            File::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT));

            $this->info("Exported {$tableName} to {$jsonPath}");
        }

        // Switch back to original connection
        config(['database.default' => $currentConnection]);

        $this->info('Export complete!');
    }
}
