<?php

namespace App\Applications\Input\Team;

use App\Constants\Pagination;
use App\Domains\Enums\Team\PublicStatus;

final class ListTeamInput
{
    public function __construct(
        /** ユーザーID @var ?string */
        public readonly ?string $userId = null,

        /** チーム名 @var ?string */
        public readonly ?string $name = null,

        /** 公開ステータス @var ?PublicStatus */
        public readonly ?PublicStatus $publicStatus = null,

        /** ページ @var int */
        public readonly int $page = Pagination::PAGE_DEFAULT,

        /** 上限 @var int */
        public readonly int $limit = Pagination::LIMIT_DEFAULT,

        /** ソート @var string */
        public readonly string $sort = Pagination::SORT_DEFAULT,
    ) {}
}
