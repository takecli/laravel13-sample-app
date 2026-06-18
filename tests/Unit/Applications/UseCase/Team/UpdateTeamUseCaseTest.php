<?php

namespace Tests\Unit\Applications\UseCase\Team;

use App\Applications\Input\Team\UpdateTeamInput;
use App\Applications\UseCase\Team\UpdateTeamUseCase;
use App\Domains\Enums\Team\PublicStatus;
use App\Domains\Models\Team as TeamEntity;
use App\Domains\Repositories\TeamRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * UpdateTeamUseCase の単体テスト。
 *
 * Repository(ポート)をモックし、Input → ドメインエンティティ(Team)への詰め替えと
 * updateTeam の呼び出し・戻り値の受け渡しだけを検証する。DB/フレームワーク非依存。
 */
final class UpdateTeamUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function inputを_teamに詰めてrepositoryのupdate_teamを呼び結果を返す(): void
    {
        // Arrange
        $input = new UpdateTeamInput(
            id: 'team-1',
            name: 'New Team',
            description: '新説明',
            publicStatus: PublicStatus::Invitation,
        );

        $updated = new TeamEntity(
            id: 'team-1',
            name: 'New Team',
            description: '新説明',
            publicStatus: PublicStatus::Invitation,
        );

        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('updateTeam')
            ->once()
            // Input が正しく Team エンティティ（id 含む）へ詰め替えられているかを検証
            ->withArgs(function (TeamEntity $team): bool {
                return $team->id === 'team-1'
                    && $team->name === 'New Team'
                    && $team->description === '新説明'
                    && $team->publicStatus === PublicStatus::Invitation;
            })
            ->andReturn($updated);

        // Act
        $result = (new UpdateTeamUseCase($repo))->execute($input);

        // Assert: Repository の戻り値がそのまま返る
        $this->assertSame($updated, $result);
    }
}
