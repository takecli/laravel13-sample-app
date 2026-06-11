<?php

use App\Domain\Enum\Notes\Status;

final class Note
{
    /** ID @var string */
    public string $id;

    /** チームID @var string */
    public string $teamId;

    /** タイトル @var string */
    public string $title;

    /** 内容 @var string */
    public string $content;

    /** ステータス @var Status */
    public Status $status;

    /** 公開日時 @var ?string */
    public string $publishedAt;
}
