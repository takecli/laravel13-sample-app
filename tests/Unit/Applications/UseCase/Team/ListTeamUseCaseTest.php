<?php

namespace Tests\Unit\Applications\UseCase\Team;

use App\Applications\Input\Team\ListTeamInput;
use App\Applications\Output\Team\ListTeamOutput;
use App\Applications\UseCase\Team\ListTeamUseCase;
use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Models\Result\ListTeamResult;
use App\Domains\Models\Team as TeamEntity;
use App\Domains\Repositories\TeamRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * UseCase層の単体テスト。
 *
 * Repository(ポート)をモックし、UseCase自身のロジックだけを検証する。
 * - Input(Application) → ListTeamSearch(Domain Filter)への詰め替え
 * - ListTeamResult(Domain) → Output(Application)への詰め替え
 * DBやフレームワークに一切依存しないため PHPUnit\Framework\TestCase を直接継承する。
 */
final class ListTeamUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function inputを_filterに変換して_repositoryを呼び_resultを_outputに変換する(): void
    {
        // Arrange
        $input = new ListTeamInput(
            userId: 'user-1',
            name: 'dev',
            page: 2,
            limit: 10,
            sort: '+name',
        );

        $team = new TeamEntity;
        $team->id = 'team-1';
        $team->name = 'dev team';

        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('listTeam')
            ->once()
            // Input が正しく ListTeamSearch へ詰め替えられているかを検証
            ->withArgs(function (ListTeamSearch $search): bool {
                return $search->userId === 'user-1'
                    && $search->name === 'dev'
                    && $search->page === 2
                    && $search->limit === 10
                    && $search->sort === '+name';
            })
            ->andReturn(new ListTeamResult([$team], 1));

        // Act
        $output = (new ListTeamUseCase($repo))->execute($input);

        // Assert
        $this->assertInstanceOf(ListTeamOutput::class, $output);
        $this->assertSame(1, $output->total);
        $this->assertCount(1, $output->teams);
        $this->assertSame('team-1', $output->teams[0]->id);
    }

    #[Test]
    public function デフォルト値の_inputでも_repositoryへ既定値が渡る(): void
    {
        $input = new ListTeamInput;

        $repo = Mockery::mock(TeamRepositoryInterface::class);
        $repo->shouldReceive('listTeam')
            ->once()
            ->withArgs(function (ListTeamSearch $search): bool {
                return $search->userId === null
                    && $search->name === null
                    && $search->page === 1     // Pagination::PAGE_DEFAULT
                    && $search->limit === 20   // Pagination::LIMIT_DEFAULT
                    && $search->sort === '+id'; // Pagination::SORT_DEFAULT
            })
            ->andReturn(new ListTeamResult([], 0));

        $output = (new ListTeamUseCase($repo))->execute($input);

        $this->assertSame(0, $output->total);
        $this->assertSame([], $output->teams);
    }
}
