<?php

namespace App\Infra\Persistence;

use App\Domains\Enums\Team\PublicStatus;
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
                $q->where('name', 'like', sprintf('%%%s%%', $name));
            })
            ->when($input->publicStatus, function (Builder $q, PublicStatus $publicStatus) {
                $q->where('public_status', $publicStatus->value);
            })
            ->simplePaginate($input->limit);

        $output = new ListTeamResult(...[
            'teams' => self::listToDomain($paginate->items()),
            'total' => $paginate->count(),
        ]);

        return $output;
    }

    #[Override]
    public function createTeam(TeamEntity $team): TeamEntity
    {
        $teamModel = TeamModel::create([
            'name' => $team->name,
            'description' => $team->description,
            'public_status' => $team->publicStatus,
        ]);

        return self::modelToDomain($teamModel);
    }

    #[Override]
    public function updateTeam(TeamEntity $team): TeamEntity
    {
        $teamModel = TeamModel::find($team->id)
            ?->fill([
                'name' => $team->name,
                'description' => $team->description,
                'public_status' => $team->publicStatus,
            ]);

        $teamModel->save();

        return self::modelToDomain($teamModel);
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
        return new TeamEntity(
            id: $teamModel->id,
            name: $teamModel->name,
            description: $teamModel->description,
            publicStatus: $teamModel->public_status,
        );
    }
}
