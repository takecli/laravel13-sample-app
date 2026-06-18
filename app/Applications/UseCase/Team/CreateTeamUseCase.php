<?php

namespace App\Applications\UseCase\Team;

use App\Applications\Input\Team\CreateTeamInput;
use App\Domains\Models\Team;
use App\Domains\Repositories\TeamRepositoryInterface;

final class CreateTeamUseCase
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepo,
    ) {}

    public function execute(CreateTeamInput $input): Team
    {
        $team = new Team(
            name: $input->name,
            description: $input->description,
            publicStatus: $input->publicStatus,
        );

        return $this->teamRepo->createTeam($team);
    }
}
