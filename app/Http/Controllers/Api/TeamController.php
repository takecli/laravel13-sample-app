<?php

namespace App\Http\Controllers\Api;

use App\Applications\Input\Team\ListTeamInput;
use App\Applications\UseCase\Team\ListTeamUseCase;
use App\Http\Controllers\Controller;
use App\Http\Reponses\ApiResponse;
use App\Http\Requests\Team\ListTeamRequest;
use App\Http\Resources\Team\TeamsResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function listTeam(ListTeamRequest $request, int $version, ListTeamUseCase $usecase): JsonResponse
    {
        try {
            $params = $request->validated();
            $res = $usecase->execute(new ListTeamInput(...$params));
            $resource = (new TeamsResource($res))->toArray($request);

            return ApiResponse::success(__('messages.success', ['action' => 'Get', 'Teams']), $resource);
        } catch (Exception $e) {
            Log::error(__('messages.error', ['action' => 'Get', 'resource' => 'Teams']));
            report($e);

            return ApiResponse::serverError();
        }
    }
}
