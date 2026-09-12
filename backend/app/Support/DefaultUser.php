<?php

namespace App\Support;

use App\Models\User;

final class DefaultUser
{
    public const EMAIL = 'admin@example.com';
    public const PASSWORD = 'password';
    public const NAME = 'Admin';
    
    public static function ensure(): User
    {
        return User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => self::NAME,
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ],
        );
    }
}
