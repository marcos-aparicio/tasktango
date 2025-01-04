<?php
namespace App\Enums;

enum ProjectUserRoles: int
{
    case OWNER = 1;  // creator of the project usually unless they assign the role to someone else
    case COLLABORATOR = 2;
    case MANAGER = 3;
}
