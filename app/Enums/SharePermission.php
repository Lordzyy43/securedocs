<?php

namespace App\Enums;

enum SharePermission: string
{
  case VIEW = 'view';
  case DOWNLOAD = 'download';

  public static function values(): array
  {
    return array_map(fn(self $permission) => $permission->value, self::cases());
  }
}
