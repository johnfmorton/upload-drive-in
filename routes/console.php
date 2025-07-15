<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:list', function () {
    $this->call('users', $this->arguments(), $this->options());
})->purpose('List all user accounts (alias for users command)');
