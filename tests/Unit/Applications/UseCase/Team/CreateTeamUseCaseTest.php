<?php

namespace Tests\Unit\Applications\UseCase\Team;

use App\Applications\Input\Team\CreateTeamInput;
use App\Applications\UseCase\Team\CreateTeamUseCase;
use App\Domains\Enums\Team\PublicStatus;
use App\Domains\Models\Team as TeamEntity;
use App\Domains\Repositories\TeamRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CreateTeamUseCase の単体テスト。
 *
 * Repository(ポート)をモックし、Input → ドメインエンティティ(Team)への詰め替えと
 * createTeam の呼び出し・戻り値の受け渡しだけを検証する。DB/フレームワーク非依存。
 */
final class CreateTeamUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function inputを_teamに詰めてrepositoryのcreate_teamを呼び結果を返す(): void
    {
        // Arrange
        $input = new CreateTeamInput(
            name: 'My Team',
            description: '開発チーム',
            publicStatus: PublicStatus::Public,
        );

        $created = new TeamEntity(
            id: 'team-1',
            name: 'My Team',
            description: '開発チーム',
            publicStatus: PublicStatus::Public,
        );

        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('createTeam')
            ->once()
            // Input が正しく Team エンティティへ詰め替えられているかを検証
            ->withArgs(function (TeamEntity $team): bool {
                return $team->name === 'My Team'
                    && $team->description === '開発チーム'
                    && $team->publicStatus === PublicStatus::Public;
            })
            ->andReturn($created);

        // Act
        $result = (new CreateTeamUseCase($repo))->execute($input);

        // Assert: Repository の戻り値がそのまま返る
        $this->assertSame($created, $result);
    }
}
