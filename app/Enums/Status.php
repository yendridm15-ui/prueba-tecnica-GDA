<?php

namespace App\Enums;

enum Status: string
{
    case Active = 'A';
    case Inactive = 'I';
    case Trash = 'T';
}
