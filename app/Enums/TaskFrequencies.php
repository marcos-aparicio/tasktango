<?php
namespace App\Enums;

enum TaskFrequencies: int
{
    case NONE = 1;
    case DAILY = 2;
    case WEEKLY = 3;
    case BIWEEKLY = 4;
    case MONTHLY = 5;
}
