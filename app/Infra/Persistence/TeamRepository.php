<?php

namespace App\Infra\Persistence;

use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Models\Result\ListTeamResult;
use App\Domains\Models\Team as TeamEntity;
use App\Domains\Repositories\TeamRepositoryInterface;
use App\Models\Team as TeamModel;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class TeamRepository implements TeamRepositoryInterface
{
    #[Override]
    public function listTeam(ListTeamSearch $input): ListTeamResult
    {
        $paginate = TeamModel::query()
            ->when($input->userId, function (Builder $q, string $userId) {
                $q->whereHas('teamUsers', fn ($q0) => $q0->where('user_id', $userId));
            })
            ->when($input->name, function (Builder $q, string $name) {
                $q->where('name', 'like', sprintf('\%%s\%', $name));
            })
            ->simplePaginate($input->limit);

        $output = new ListTeamResult(...[
            'teams' => self::listToDomain($paginate->items()),
            'total' => $paginate->count(),
        ]);

        return $output;
    }

    #[Override]
    public static function listToDomain(array $teamModels): array
    {
        $teams = [];
        foreach ($teamModels as $teamModel) {
            $teams[] = self::modelToDomain($teamModel);
        }

        return $teams;
    }

    #[Override]
    public static function modelToDomain(TeamModel $teamModel): TeamEntity
    {
        $team = new TeamEntity;
        $team->id = $teamModel->id;
        $team->name = $teamModel->name;
        $team->description = $teamModel->description;
        $team->publicStatus = $teamModel->public_status;

        return $team;
    }
}
