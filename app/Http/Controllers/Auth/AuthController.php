<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Reponses\ApiResponse;
use App\Http\Resources\Auth\UserResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * 認証情報取得
     *
     * @param  Request  $request
     * @param  int  $vesrion
     * @return JsonResponse
     */
    public function getUser(Request $request, int $vesrion): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = (new UserResource($user))->toArray($request);

            return ApiResponse::success(__('success', ['action' => 'Get', 'resource' => 'User']), $data);
        } catch (Exception $e) {
            $msg = __('messages.error', ['action' => 'Get', 'resource' => 'User']);
            Log::error($msg);
            report($e);

            return ApiResponse::serverError($msg);
        }
    }
}
