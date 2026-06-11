<?php

namespace App\Domain\Enum\Notes;

enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
}
