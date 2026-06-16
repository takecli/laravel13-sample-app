<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * KeycloakController(Keycloak OAuth)のFeatureテスト。
 *
 * Socialite ファサードをモックして外部IdPへの通信を遮断し、
 * - redirect: 認可URLへのリダイレクト成功 / 例外時の 500
 * - callback: ユーザー作成・ログイン後のリダイレクト成功 / 例外時の /error 遷移
 * を検証する。
 */
final class KeycloakControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    #[Test]
    public function redirect_正常系_認可urlへリダイレクトする(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->andReturn(new RedirectResponse('https://keycloak.example/auth'));
        Socialite::shouldReceive('driver')->with('keycloak')->andReturn($provider);

        $res = $this->get('/api/v1/auth/keycloak/redirect');

        $res->assertRedirect('https://keycloak.example/auth');
    }

    #[Test]
    public function redirect_例外発生時は500を返す(): void
    {
        Socialite::shouldReceive('driver')->andThrow(new Exception('idp down'));

        $res = $this->getJson('/api/v1/auth/keycloak/redirect');

        $res->assertStatus(500)
            ->assertJsonPath('result', false);
    }

    #[Test]
    public function callback_正常系_ユーザーを作成しログインしてダッシュボードへ遷移する(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('kc-123');
        $socialUser->shouldReceive('getName')->andReturn('山田太郎');
        $socialUser->shouldReceive('getEmail')->andReturn('taro@example.com');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('keycloak')->andReturn($provider);

        $res = $this->get('/api/v1/auth/keycloak/callback');

        $res->assertRedirect('/dashbord');
        $this->assertDatabaseHas('users', [
            'keycloak_id' => 'kc-123',
            'email' => 'taro@example.com',
        ]);
        $this->assertTrue(Auth::check());
        $this->assertSame('taro@example.com', User::query()->first()->email);
    }

    #[Test]
    public function callback_例外発生時はエラーページへリダイレクトする(): void
    {
        Socialite::shouldReceive('driver')->andThrow(new Exception('idp down'));

        $res = $this->get('/api/v1/auth/keycloak/callback');

        $res->assertRedirect('/error');
        $this->assertFalse(Auth::check());
    }
}
