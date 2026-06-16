<?php

namespace Tests\Unit\Http\Requests\Team;

use App\Http\Requests\Team\ListTeamRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ListTeamRequest の単体テスト。
 *
 * FormRequest の authorize()/rules() は純粋な戻り値メソッドなので、
 * フレームワークを起動せず直接インスタンス化して検証する。
 */
final class ListTeamRequestTest extends TestCase
{
    #[Test]
    public function authorize_は常に許可する(): void
    {
        $this->assertTrue((new ListTeamRequest)->authorize());
    }

    #[Test]
    public function rules_は_page_limit_sort_の検証ルールを返す(): void
    {
        $rules = (new ListTeamRequest)->rules();

        $this->assertSame(['nullable', 'integer'], $rules['page']);
        $this->assertSame(['nullable', 'integer'], $rules['limit']);
        $this->assertSame(['nullable', 'string'], $rules['sort']);
    }
}
