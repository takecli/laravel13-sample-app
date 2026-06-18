<?php

namespace Tests\Unit\Http\Resources;

use App\Applications\Output\Team\ListTeamOutput;
use App\Domains\Enums\Team\PublicStatus;
use App\Domains\Models\Team as TeamEntity;
use App\Http\Resources\Team\TeamsResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TeamsResource の単体テスト。
 *
 * resource(ListTeamOutput)の teams/total を整形して返すだけなので、
 * フレームワークもDBも使わずドメインエンティティを直接組み立てて検証する。
 */
final class TeamsResourceTest extends TestCase
{
    #[Test]
    public function teams配列を整形し_totalを付与する(): void
    {
        $team = new TeamEntity(
            id: 'team-1',
            name: 'dev team',
            description: '開発チーム',
            publicStatus: PublicStatus::Public,
        );

        $output = new ListTeamOutput([$team], 1);

        $array = (new TeamsResource($output))->toArray(Request::create('/'));

        $this->assertSame(1, $array['total']);
        $this->assertCount(1, $array['teams']);
        $this->assertSame([
            'id' => 'team-1',
            'name' => 'dev team',
            'description' => '開発チーム',
            'public_status' => PublicStatus::Public,
        ], $array['teams'][0]);
    }

    #[Test]
    public function teamsが空でも_totalゼロで返る(): void
    {
        $output = new ListTeamOutput([], 0);

        $array = (new TeamsResource($output))->toArray(Request::create('/'));

        $this->assertSame(['teams' => [], 'total' => 0], $array);
    }
}
