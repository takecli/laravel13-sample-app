<?php

namespace App\Domains\Models;

use App\Domains\Enums\Team\PublicStatus;

final class Team
{
    public function __construct(
        /** ID @var string */
        public readonly string $id = '',

        /** チーム名 @var string */
        public readonly string $name = '',

        /** 説明 @var string */
        public readonly string $description = '',

        /** 公開ステータス @var PublicStatus */
        public readonly PublicStatus $publicStatus = PublicStatus::Public,

        /** 作成日 @var string */
        public readonly string $createdAt = '',

        /** 更新日 @var string */
        public readonly string $updatedAt = '',

        /** 作成ユーザーID @var string */
        public readonly string $createdUserId = '',

        /** 更新ユーザーID @var string */
        public readonly string $updatedUserId = '',
    ) {}
}
