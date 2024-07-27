<?php

namespace App\Entity;

enum Priority: string
{
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
  case PRIORITY_HIGHT = "alta";
  case PRIORITY_MEDIUM = "media";
  case PRIORITY_LOW = "baja";
}


enum Status: string
{
  case STATUS_PENDING = "pendiente";
  case STATUS_COMPLETED = "completada";
}