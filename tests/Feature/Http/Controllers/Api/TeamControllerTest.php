<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Domains\Repositories\TeamRepositoryInterface;
use App\Models\Team as TeamModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * TeamController(チームAPI)のFeatureテスト。
 *
 * teams ルートは auth ミドルウェア必須のため、各テストで actingAs して認証済みにする。
 * 実ルート（一覧/作成/更新）を叩き、正常系・バリデーション・例外(rollBack)・未認証(401)を検証する。
 */
final class TeamControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    #[Test]
    public function 未認証は401を返す(): void
    {
        // actingAs しない（auth ミドルウェアで弾かれる）
        $res = $this->getJson('/api/v1/teams');

        $res->assertStatus(401)
            ->assertJsonPath('result', false);
    }

    #[Test]
    public function 正常系_チーム一覧を200で返す(): void
    {
        $this->actingAs(User::factory()->create());
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
        $this->actingAs(User::factory()->create());
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
        $this->actingAs(User::factory()->create());

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
        $this->actingAs(User::factory()->create());

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
        $this->actingAs(User::factory()->create());

        $res = $this->postJson('/api/v1/teams', []);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'public_status']);

        $this->assertDatabaseCount('teams', 0);
    }

    #[Test]
    public function create_team_例外時は500を返しロールバックする(): void
    {
        $this->actingAs(User::factory()->create());

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

    #[Test]
    public function update_team_正常系_チームを更新して返す(): void
    {
        $this->actingAs(User::factory()->create());

        $team = TeamModel::factory()->create([
            'name' => 'Old Team',
            'description' => '旧説明',
            'public_status' => 'public',
        ]);

        $res = $this->putJson("/api/v1/teams/{$team->id}", [
            'name' => 'New Team',
            'description' => '新説明',
            'public_status' => 'invitation',
        ]);

        $res->assertOk()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.id', $team->id)
            ->assertJsonPath('data.name', 'New Team')
            ->assertJsonPath('data.description', '新説明')
            ->assertJsonPath('data.public_status', 'invitation');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'New Team',
            'description' => '新説明',
            'public_status' => 'invitation',
        ]);
    }

    #[Test]
    public function update_team_存在しない_i_dは422になる(): void
    {
        $this->actingAs(User::factory()->create());

        $res = $this->putJson('/api/v1/teams/00000000-0000-0000-0000-000000000000', [
            'name' => 'New Team',
            'description' => '新説明',
            'public_status' => 'public',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    #[Test]
    public function update_team_必須項目が無いと422になる(): void
    {
        $this->actingAs(User::factory()->create());

        $team = TeamModel::factory()->create();

        $res = $this->putJson("/api/v1/teams/{$team->id}", []);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'public_status']);
    }

    #[Test]
    public function update_team_例外時は500を返しロールバックする(): void
    {
        $this->actingAs(User::factory()->create());

        $team = TeamModel::factory()->create(['name' => 'Old Team']);

        // Repository を例外送出モックに差し替え、catch(rollBack)分岐へ入れる
        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('updateTeam')->andThrow(new RuntimeException('boom'));
        $this->app->instance(TeamRepositoryInterface::class, $repo);

        $res = $this->putJson("/api/v1/teams/{$team->id}", [
            'name' => 'New Team',
            'description' => '新説明',
            'public_status' => 'public',
        ]);

        $res->assertStatus(500)
            ->assertJsonPath('result', false);
        // ロールバックで更新前の値のまま
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Old Team']);
    }
}
