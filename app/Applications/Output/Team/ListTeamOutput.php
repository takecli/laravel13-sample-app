<?php

namespace App\Applications\Output\Team;

final class ListTeamOutput
{
    public function __construct(
        /** チーム一覧 @var array<Team> */
        public readonly array $teams,

        /** 全件数 @var int */
        public readonly int $total,
    ) {}
}
