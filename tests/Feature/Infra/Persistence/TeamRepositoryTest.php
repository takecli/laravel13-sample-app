<?php

namespace Tests\Feature\Infra\Persistence;

use App\Domains\Enums\Team\PublicStatus;
use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Models\Result\ListTeamResult;
use App\Domains\Models\Team as TeamEntity;
use App\Infra\Persistence\TeamRepository;
use App\Models\Team as TeamModel;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Infra層(Repository実装)の統合テスト。
 *
 * 実DBへクエリを投げるため Laravel をブートする Tests\TestCase を継承し、
 * RefreshDatabase で各テストごとにマイグレーション + ロールバックを行う。
 *
 * 【注意】本プロジェクトの migration は MySQL 専用の生SQL(UUID_TO_BIN / ENUM / COMMENT)
 * のため、phpunit.xml 既定の sqlite :memory: では実行できない。
 * MySQL のテスト用DB接続を用意するか、migration を Schema ビルダーで書き直すこと。
 */
final class TeamRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TeamRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TeamRepository;
    }

    #[Test]
    public function 条件なしで全件取得しドメインモデルへ変換する(): void
    {
        TeamModel::factory()->count(3)->create();

        $result = $this->repository->listTeam(
            new ListTeamSearch(null, null, null, 1, 20, '+id')
        );

        $this->assertInstanceOf(ListTeamResult::class, $result);
        $this->assertCount(3, $result->teams);
        // Eloquent Model ではなく Domain エンティティへ変換されていること
        $this->assertInstanceOf(TeamEntity::class, $result->teams[0]);
        $this->assertInstanceOf(PublicStatus::class, $result->teams[0]->publicStatus);
    }

    #[Test]
    public function nameで部分一致フィルタする(): void
    {
        TeamModel::factory()->create(['name' => 'Engineering Team']);
        TeamModel::factory()->create(['name' => 'Sales Team']);

        $result = $this->repository->listTeam(
            new ListTeamSearch(null, 'Engineering', null, 1, 20, '+id')
        );

        $this->assertCount(1, $result->teams);
        $this->assertSame('Engineering Team', $result->teams[0]->name);
    }

    #[Test]
    public function user_idで所属チームのみ取得する(): void
    {
        $userId = (string) Str::uuid();
        $mine = TeamModel::factory()->create();
        TeamModel::factory()->create(); // 別チーム(所属していない)

        TeamUser::create([
            'team_id' => $mine->id,
            'user_id' => $userId,
            'role' => 'member',
        ]);

        $result = $this->repository->listTeam(
            new ListTeamSearch($userId, null, null, 1, 20, '+id')
        );

        $this->assertCount(1, $result->teams);
        $this->assertSame($mine->id, $result->teams[0]->id);
    }

    #[Test]
    public function limitで1ページあたりの件数を絞る(): void
    {
        TeamModel::factory()->count(5)->create();

        $result = $this->repository->listTeam(
            new ListTeamSearch(null, null, null, 1, 2, '+id')
        );

        $this->assertCount(2, $result->teams);
    }

    #[Test]
    public function public_statusでフィルタする(): void
    {
        TeamModel::factory()->create();               // public（ファクトリ既定）
        TeamModel::factory()->invitation()->create(); // invitation

        $result = $this->repository->listTeam(
            new ListTeamSearch(null, null, PublicStatus::Invitation, 1, 20, '+id')
        );

        $this->assertCount(1, $result->teams);
        $this->assertSame(PublicStatus::Invitation, $result->teams[0]->publicStatus);
    }
}
