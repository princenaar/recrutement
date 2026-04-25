<?php

namespace App\Enums;

enum InvitationChannel: string
{
    case Email = 'email';
    case Manual = 'manual';
}
