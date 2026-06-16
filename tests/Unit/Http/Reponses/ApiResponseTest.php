<?php

namespace Tests\Unit\Http\Reponses;

use App\Http\Reponses\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ApiResponse(共通レスポンスビルダー)の単体テスト。
 *
 * response() ヘルパ(コンテナ)に依存するため Laravel をブートする Tests\TestCase を継承する。
 * DBには触れないので RefreshDatabase は不要。
 * 正常系/各エラー系で「HTTPステータス」「result フラグ」「message」「data」が
 * 仕様どおり組み立てられることを検証する。
 */
final class ApiResponseTest extends TestCase
{
    #[Test]
    public function success_は200と_resultトゥルーで_dataとmessageを返す(): void
    {
        $res = ApiResponse::success('done', ['foo' => 'bar']);

        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertSame(JsonResponse::HTTP_OK, $res->getStatusCode());
        $this->assertSame([
            'data' => ['foo' => 'bar'],
            'message' => 'done',
            'result' => true,
        ], $res->getData(true));
    }

    #[Test]
    public function success_はステータスを上書きできる(): void
    {
        $res = ApiResponse::success('created', null, JsonResponse::HTTP_CREATED);

        $this->assertSame(JsonResponse::HTTP_CREATED, $res->getStatusCode());
        $this->assertTrue($res->getData(true)['result']);
    }

    #[Test]
    public function bad_request_は400で_resultフォールスを返す(): void
    {
        $res = ApiResponse::badRequest();

        $this->assertSame(JsonResponse::HTTP_BAD_REQUEST, $res->getStatusCode());
        $this->assertFalse($res->getData(true)['result']);
        $this->assertSame('Bad Request', $res->getData(true)['message']);
    }

    #[Test]
    public function unauthenticate_は401を返す(): void
    {
        $res = ApiResponse::unauthenticate();

        $this->assertSame(JsonResponse::HTTP_UNAUTHORIZED, $res->getStatusCode());
        $this->assertSame('Unauthenticate.', $res->getData(true)['message']);
    }

    #[Test]
    public function forbidden_は403を返す(): void
    {
        $res = ApiResponse::forbidden();

        $this->assertSame(JsonResponse::HTTP_FORBIDDEN, $res->getStatusCode());
        $this->assertSame('Unauthorized.', $res->getData(true)['message']);
    }

    #[Test]
    public function not_found_は404を返す(): void
    {
        $res = ApiResponse::notFound();

        $this->assertSame(JsonResponse::HTTP_NOT_FOUND, $res->getStatusCode());
        $this->assertSame('NotFound', $res->getData(true)['message']);
    }

    #[Test]
    public function server_error_は500を返す(): void
    {
        $res = ApiResponse::serverError('boom', ['detail' => 'x']);

        $this->assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $res->getStatusCode());
        $this->assertFalse($res->getData(true)['result']);
        $this->assertSame('boom', $res->getData(true)['message']);
        $this->assertSame(['detail' => 'x'], $res->getData(true)['data']);
    }
}
