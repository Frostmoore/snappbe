<?php

namespace App\Enums;

enum AccountLinkStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
}
