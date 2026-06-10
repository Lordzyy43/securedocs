<?php

namespace App\Enums;

enum UserRole: string
{
  case ADMIN = 'admin';
  case USER = 'user';

  public static function values(): array
  {
    return array_map(fn(self $role) => $role->value, self::cases());
  }
}
