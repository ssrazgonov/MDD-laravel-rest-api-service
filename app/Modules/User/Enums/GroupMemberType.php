<?php

namespace App\Modules\User\Enums;

enum GroupMemberType: string
{
    case DEPENDENT = 'dependent';
    case PRIMARY = 'primary';
}
