<?php

namespace App\Http\Controllers\Api;

use App\Applications\UseCase\Team\CreateTeamUseCase;
use App\Applications\UseCase\Team\ListTeamUseCase;
use App\Http\Controllers\Controller;
use App\Http\Reponses\ApiResponse;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\ListTeamRequest;
use App\Http\Resources\Team\TeamsResource;
use App\Http\Resources\TeamResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * チーム一覧
     *
     * @param  ListTeamRequest  $request
     * @param  int  $version
     * @param  ListTeamUseCase  $usecase
     * @return JsonResponse
     */
    public function listTeam(ListTeamRequest $request, int $version, ListTeamUseCase $usecase): JsonResponse
    {
        try {
            $res = $usecase->execute($request->toInput());
            $resource = (new TeamsResource($res))->toArray($request);

            return ApiResponse::success(__('messages.success', ['action' => 'Get', 'Teams']), $resource);
        } catch (Exception $e) {
            Log::error(__('messages.error', ['action' => 'Get', 'resource' => 'Teams']));
            report($e);

            return ApiResponse::serverError();
        }
    }

    /**
     * チーム作成
     *
     * @param  CreateTeamRequest  $request
     * @param  int  $version
     * @param  CreateTeamUseCase  $usecase
     * @return JsonResponse
     */
    public function createTeam(CreateTeamRequest $request, int $version, CreateTeamUseCase $usecase): JsonResponse
    {
        DB::beginTransaction();
        try {
            $res = $usecase->execute($request->toInput());
            $resource = (new TeamResource($res))->toArray($request);

            DB::commit();

            return ApiResponse::success(__('messages.success', ['action' => 'Get', 'Teams']), $resource);
        } catch (Exception $e) {
            Log::error(__('messages.error', ['action' => 'Create', 'resource' => 'Team']));
            report($e);
            DB::rollBack();

            return ApiResponse::serverError();
        }
    }
}
