<?php

namespace App\Entity;

enum Priority: string
{
  case PRIORITY_HIGHT = "alta";
  case PRIORITY_MEDIUM = "media";
  case PRIORITY_LOW = "baja";
}


enum Status: string
{
  case STATUS_PENDING = "pendiente";
  case STATUS_COMPLETED = "completada";
}