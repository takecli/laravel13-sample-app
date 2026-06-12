<?php

namespace App\Domains\Models\Filter;

final class ListTeamSearch
{
    public function __construct(
        /** ユーザーID @var ?string */
        public readonly ?string $userId,

        /** チーム名 @var ?string */
        public readonly ?string $name,

        /** ページ @var int */
        public readonly int $page,

        /** 上限 @var int */
        public readonly int $limit,

        /** ソート @var string */
        public readonly string $sort,
    ) {}
}
