<?php

namespace App\Applications\Input\Team;

use App\Domains\Enums\Team\PublicStatus;

final class CreateTeamInput
{
    public function __construct(
        /** チーム名 @var string */
        public readonly string $name,

        /** チーム説明 @var string */
        public readonly string $description,

        /** 公開状況 @var PublicStatus */
        public readonly PublicStatus $publicStatus,
    ) {}
}
