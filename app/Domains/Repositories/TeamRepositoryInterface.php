<?php

namespace App\Domains\Repositories;

use App\Domains\Models\Filter\ListTeamSearch;
use App\Domains\Models\Result\ListTeamResult;
use App\Domains\Models\Team as TeamEntity;
use App\Models\Team as TeamModel;

interface TeamRepositoryInterface
{
    /**
     * チーム一覧取得
     *
     * @param  ListTeamSearch  $input
     * @return ListTeamResult
     */
    public function listTeam(ListTeamSearch $input): ListTeamResult;

    /**
     * チーム作成
     *
     * @param  TeamEntity  $team
     * @return TeamEntity
     */
    public function createTeam(TeamEntity $team): TeamEntity;

    /**
     * チーム更新
     *
     * @param  TeamEntity  $team
     * @return TeamEntity
     */
    public function updateTeam(TeamEntity $team): TeamEntity;

    /**
     * Model一覧をドメインモデル一覧へ変換
     *
     * @param  array  $teamModels
     * @return array<TeamEntity>
     */
    public static function listToDomain(array $teamModels): array;

    /**
     * Modelをドメインモデルへ変換
     *
     * @param  TeamModel  $team
     * @return TeamEntity
     */
    public static function modelToDomain(TeamModel $team): TeamEntity;
}
