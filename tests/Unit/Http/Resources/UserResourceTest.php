<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * UserResource の単体テスト。
 *
 * JsonResource::toArray は resource のプロパティを読むだけなので、
 * フレームワークもDBも使わず、プロパティを持つ素のオブジェクトを resource に渡して検証する。
 */
final class UserResourceTest extends TestCase
{
    #[Test]
    public function id_name_email_のみを抽出する(): void
    {
        $user = (object) [
            'id' => 'user-1',
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'keycloak_id' => 'should-not-appear',
        ];

        $array = (new UserResource($user))->toArray(Request::create('/'));

        $this->assertSame([
            'id' => 'user-1',
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ], $array);
    }
}
