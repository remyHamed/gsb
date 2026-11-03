<?php
namespace App\Enum;

enum Role: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case MANAGER  = 'MANAGER';
    case ADMIN    = 'ADMIN';
}