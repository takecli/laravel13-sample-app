<?php

namespace App\Domains\Models\Result;

final class ListTeamResult
{
    public function __construct(
        /** チーム一覧 @var array<Team> */
        public readonly array $teams,

        /** 全件数 @var int */
        public readonly int $total,
    ) {}
}
