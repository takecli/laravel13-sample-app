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
    public function public_statusで絞り込める(): void
    {
        TeamModel::factory()->create();               // public（ファクトリ既定）
        TeamModel::factory()->invitation()->create(); // invitation

        $res = $this->getJson('/api/v1/teams?public_status=invitation');

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonCount(1, 'data.teams')
            ->assertJsonPath('data.teams.0.public_status', 'invitation');
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

    #[Test]
    public function create_team_正常系_チームを作成して返す(): void
    {
        $payload = [
            'name' => 'My Team',
            'description' => '開発チーム',
            'public_status' => 'public',
        ];

        $res = $this->postJson('/api/v1/teams', $payload);

        $res->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.name', 'My Team')
            ->assertJsonPath('data.description', '開発チーム')
            ->assertJsonPath('data.public_status', 'public');

        $this->assertDatabaseHas('teams', [
            'name' => 'My Team',
            'description' => '開発チーム',
            'public_status' => 'public',
        ]);
    }

    #[Test]
    public function create_team_必須項目が無いと422になる(): void
    {
        $res = $this->postJson('/api/v1/teams', []);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'public_status']);

        $this->assertDatabaseCount('teams', 0);
    }

    #[Test]
    public function create_team_例外時は500を返しロールバックする(): void
    {
        // Repository を例外送出モックに差し替え、catch(rollBack)分岐へ入れる
        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('createTeam')->andThrow(new RuntimeException('boom'));
        $this->app->instance(TeamRepositoryInterface::class, $repo);

        $res = $this->postJson('/api/v1/teams', [
            'name' => 'My Team',
            'description' => '開発チーム',
            'public_status' => 'public',
        ]);

        $res->assertStatus(500)
            ->assertJsonPath('result', false);
        $this->assertDatabaseCount('teams', 0);
    }
}
