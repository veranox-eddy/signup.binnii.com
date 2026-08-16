<?php

// Mirrors app.binnii.com/app/Enums/UserType.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserType: string
{
    use HasValues;

    case Admin = 'admin';
    case Teacher = 'teacher';
    case ClassroomLogin = 'classroom_login';
}
