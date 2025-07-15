<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users {--format=table : Output format (table, json, csv)}';

    /**
     * List of command aliases.
     *
     * @var array
     */
    protected $aliases = ['users:list'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all user accounts with their username, email, and role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        
        $users = User::select('id', 'name', 'username', 'email', 'role', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

        if ($users->isEmpty()) {
            $this->info('No users found in the database.');
            return self::SUCCESS;
        }

        switch ($format) {
            case 'json':
                $this->outputJson($users);
                break;
            case 'csv':
                $this->outputCsv($users);
                break;
            case 'table':
            default:
                $this->outputTable($users);
                break;
        }

        $this->info("\nTotal users: " . $users->count());
        
        return self::SUCCESS;
    }

    /**
     * Output users in table format.
     */
    private function outputTable($users): void
    {
        $headers = ['ID', 'Name', 'Username', 'Email', 'Role', 'Created'];
        
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name ?? 'N/A',
                $user->username ?? 'N/A',
                $user->email,
                $user->role->label(),
                $user->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * Output users in JSON format.
     */
    private function outputJson($users): void
    {
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'created_at' => $user->created_at->toISOString(),
            ];
        });

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Output users in CSV format.
     */
    private function outputCsv($users): void
    {
        // Output CSV header
        $this->line('ID,Name,Username,Email,Role,Created');
        
        // Output CSV rows
        $users->each(function ($user) {
            $this->line(sprintf(
                '%d,"%s","%s","%s","%s","%s"',
                $user->id,
                $this->escapeCsv($user->name ?? ''),
                $this->escapeCsv($user->username ?? ''),
                $this->escapeCsv($user->email),
                $this->escapeCsv($user->role->label()),
                $user->created_at->format('Y-m-d H:i:s')
            ));
        });
    }

    /**
     * Escape CSV field values.
     */
    private function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        
        return str_replace('"', '""', $value);
    }
}