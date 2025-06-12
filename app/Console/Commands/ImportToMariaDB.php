<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportToMariaDB extends Command
{
    protected $signature = 'db:import-mariadb';
    protected $description = 'Import JSON data into MariaDB';

    public function handle()
    {
        $exportPath = storage_path('app/private/db_export');

        if (!File::exists($exportPath)) {
            $this->error('No export directory found!');
            return 1;
        }

        $files = File::files($exportPath);
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        foreach ($files as $file) {
            $tableName = pathinfo($file, PATHINFO_FILENAME);
            $this->info("Importing table: {$tableName}");

            // Read JSON data
            $jsonData = json_decode(File::get($file), true);

            if (empty($jsonData)) {
                $this->warn("No data found for table: {$tableName}");
                continue;
            }

            // Handle foreign key checks based on database type
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } else if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            try {
                // Clear existing data
                DB::table($tableName)->truncate();

                // Insert data in chunks
                foreach (array_chunk($jsonData, 100) as $chunk) {
                    DB::table($tableName)->insert($chunk);
                }
            } finally {
                // Re-enable foreign key checks
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } else if ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                }
            }

            $this->info("Imported {$tableName}");
        }

        $this->info('Import complete!');
    }
}
