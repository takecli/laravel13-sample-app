<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Domains\Repositories\TeamRepositoryInterface;
use App\Models\Team as TeamModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * TeamController(チーム一覧API)のFeatureテスト。
 *
 * 実ルート GET /api/v1/teams を叩き、コントローラ→UseCase→Repository→Resource→ApiResponse
 * の正常系と、例外発生時の catch(500)分岐の双方を検証する。
 */
final class TeamControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    #[Test]
    public function 正常系_チーム一覧を200で返す(): void
    {
        TeamModel::factory()->count(2)->create();

        $res = $this->getJson('/api/v1/teams');

        $res->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(2, 'data.teams');
    }

    #[Test]
    public function 例外発生時は500で_resultフォールスを返す(): void
    {
        // Repository を例外送出モックに差し替え、catch 分岐へ入れる
        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('listTeam')->andThrow(new RuntimeException('boom'));
        $this->app->instance(TeamRepositoryInterface::class, $repo);

        $res = $this->getJson('/api/v1/teams');

        $res->assertStatus(500)
            ->assertJsonPath('result', false);
    }
}
