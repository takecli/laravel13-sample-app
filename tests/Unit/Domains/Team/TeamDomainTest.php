<?php

namespace Tests\Unit\Domains\Team;

use App\Domains\Enums\Team\PublicStatus;
use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Models\Result\ListTeamResult;
use App\Domains\Models\Team as TeamEntity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Domain層の単体テスト。
 *
 * Domainは純粋なPHPオブジェクト(値オブジェクト・enum・エンティティ)なので
 * フレームワークもDBも使わずに検証できる。現状ロジックは無いが、
 * 不変条件やenumの値が壊れていないことを担保する回帰テストとして機能する。
 */
final class TeamDomainTest extends TestCase
{
    #[Test]
    public function public_status_文字列から生成できる(): void
    {
        $this->assertSame(PublicStatus::Public, PublicStatus::from('public'));
        $this->assertSame(PublicStatus::Invitation, PublicStatus::from('invitation'));
    }

    #[Test]
    public function public_status_未定義の値は_value_error(): void
    {
        $this->expectException(\ValueError::class);
        PublicStatus::from('unknown');
    }

    #[Test]
    public function list_team_search_に渡した値を保持する(): void
    {
        $search = new ListTeamSearch('user-1', 'dev', PublicStatus::Public, 2, 10, '+name');

        $this->assertSame('user-1', $search->userId);
        $this->assertSame('dev', $search->name);
        $this->assertSame(PublicStatus::Public, $search->publicStatus);
        $this->assertSame(2, $search->page);
        $this->assertSame(10, $search->limit);
        $this->assertSame('+name', $search->sort);
    }

    #[Test]
    public function list_team_result_はチーム一覧と総件数を保持する(): void
    {
        $team = new TeamEntity(id: 'team-1');

        $result = new ListTeamResult([$team], 1);

        $this->assertCount(1, $result->teams);
        $this->assertSame('team-1', $result->teams[0]->id);
        $this->assertSame(1, $result->total);
    }
}
