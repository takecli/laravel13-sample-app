<?php

namespace App\Domains\Models;

use PublicStatus;

final class Team
{
    /** ID @var string */
    public string $id;

    /** チーム名 @var string */
    public string $name;

    /** 説明 @var string */
    public string $description;

    /** 公開ステータス @var PublicStatus */
    public PublicStatus $publicStatus;

    /** 作成日 @var string */
    public string $createdAt;

    /** 更新日 @var string */
    public string $updatedAt;

    /** 作成ユーザーID @var string */
    public string $createdUserId;

    /** 更新ユーザーID @var string */
    public string $updatedUserId;
}
