<?php

namespace App\Domains\Enums\Team;

enum PublicStatus: string
{
    case Invitation = 'invitation';
    case Public = 'public';
}
