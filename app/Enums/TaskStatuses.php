<?php
namespace App\Enums;

enum TaskStatuses: int
{
    case PENDING = 1;
    case COMPLETED = 2;
    case DELETED = 3;
}
