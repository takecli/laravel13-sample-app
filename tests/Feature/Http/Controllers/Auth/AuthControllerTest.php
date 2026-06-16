<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AuthController(認証情報取得API)のFeatureテスト。
 *
 * 実ルート GET /api/v1/auth を叩き、認証ユーザーの取得→UserResource→ApiResponse の
 * 正常系と、Auth::user() が例外を投げた場合の catch(500)分岐の双方を検証する。
 */
final class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 正常系_認証ユーザー情報を200で返す(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/v1/auth');

        $res->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    }

    #[Test]
    public function 例外発生時は500で_resultフォールスを返す(): void
    {
        // Auth::user() を例外送出にして catch 分岐へ入れる
        Auth::shouldReceive('user')->andThrow(new Exception('boom'));

        $res = $this->getJson('/api/v1/auth');

        $res->assertStatus(500)
            ->assertJsonPath('result', false);
    }
}
