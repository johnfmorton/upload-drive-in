<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::CLIENT => 'Client',
            self::EMPLOYEE => 'Employee',
        };
    }

    public function canLoginWithPassword(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::CLIENT => true,
            self::EMPLOYEE => true,
        };
    }
}
