<?php

namespace App\Enums;

enum ShareStatus: string
{
  case SENT = 'sent';
  case READ = 'read';
  case DOWNLOADED = 'downloaded';
  case REVOKED = 'revoked';

  public static function values(): array
  {
    return array_map(fn(self $status) => $status->value, self::cases());
  }
}
