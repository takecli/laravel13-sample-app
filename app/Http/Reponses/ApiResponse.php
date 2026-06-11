<?php

namespace App\Http\Reponses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * 正常系
     *
     * @param  string  $message
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     */
    public static function success(
        string $message,
        mixed $data,
        int $status = JsonResponse::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        return self::json(true, $message, $data, $status, $headers);
    }

    /**
     * 400 - 不正なリクエスト
     *
     * @param  string  $message
     * @param  null|mixed  $data
     */
    public static function badRequest(string $message = 'Bad Request', mixed $data = null): JsonResponse
    {
        return self::json(false, $message, $data, JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * 401 - 認証エラー
     *
     * @param  string  $message
     * @param  null|mixed  $data
     */
    public static function unauthenticate(string $message = 'Unauthenticate.', mixed $data = null): JsonResponse
    {
        return self::json(false, $message, $data, JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * 403 - 認可エラー
     *
     * @param  string  $message
     * @param  null|mixed  $data
     */
    public static function forbidden(string $message = 'Unauthorized.', mixed $data = null): JsonResponse
    {
        return self::json(false, $message, $data, JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * 404 - 不明リソース
     *
     * @param  string  $message
     * @param  null|mixed  $data
     */
    public static function notFound(string $message = 'NotFound', mixed $data = null): JsonResponse
    {
        return self::json(false, $message, $data, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * 500 - サーバーエラー
     *
     * @param  string  $message
     * @param  null|mixed  $data
     */
    public static function serverError(string $message = 'Server Error', mixed $data = null): JsonResponse
    {
        return self::json(false, $message, $data, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private static function json(
        bool $result,
        string|array $message,
        mixed $data,
        int $status,
        ?array $headers = []
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'result' => $result,
        ], $status, $headers, JSON_UNESCAPED_UNICODE);
    }
}
