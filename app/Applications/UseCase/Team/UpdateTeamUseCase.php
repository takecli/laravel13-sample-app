<?php

namespace App\Applications\UseCase\Team;

use App\Applications\Input\Team\UpdateTeamInput;
use App\Domains\Models\Team;
use App\Domains\Repositories\TeamRepositoryInterface;

final class UpdateTeamUseCase
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepo,
    ) {}

    public function execute(UpdateTeamInput $input): Team
    {
        $team = new Team(
            id: $input->id,
            name: $input->name,
            description: $input->description,
            publicStatus: $input->publicStatus,
        );

        return $this->teamRepo->updateTeam($team);
    }
}
