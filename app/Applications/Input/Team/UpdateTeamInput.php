<?php

namespace App\Applications\Input\Team;

use App\Domains\Enums\Team\PublicStatus;

final class UpdateTeamInput
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly PublicStatus $publicStatus,
    ) {}
}
