<?php

namespace App\Applications\UseCase\Team;

use App\Applications\Input\Team\ListTeamInput;
use App\Applications\Output\Team\ListTeamOutput;
use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Repositories\TeamRepositoryInterface;

final class ListTeamUseCase
{
    public function __construct(
        private TeamRepositoryInterface $teamRepo,
    ) {}

    /**
     * チーム一覧取得
     *
     * @param  ListTeamInput  $input
     * @return ListTeamOutput
     */
    public function execute(ListTeamInput $input): ListTeamOutput
    {
        $result = $this->teamRepo->listTeam(new ListTeamSearch(
            $input->userId,
            $input->name,
            $input->publicStatus,
            $input->page,
            $input->limit,
            $input->sort
        ));

        return new ListTeamOutput($result->teams, $result->total);
    }
}
