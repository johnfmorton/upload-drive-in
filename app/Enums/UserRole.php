<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::CLIENT => 'Client',
        };
    }

    public function canLoginWithPassword(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::CLIENT => false,
        };
    }
}
