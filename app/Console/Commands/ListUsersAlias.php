<?php

namespace App\Console\Commands;

/**
 * Alias command for users:list that delegates to user:list
 * 
 * This provides the users:list alias while maintaining the original user:list command.
 * Both commands work identically and accept the same options.
 * 
 * Examples:
 * php artisan users:list
 * php artisan users:list --role=admin
 * php artisan users:list --role=employee --owner=admin@example.com
 */
class ListUsersAlias extends ListUsers
{
    protected $signature = 'users:list {--role=} {--owner=}';
    protected $description = 'List all users or filter by role and owner (alias for user:list)';
}