<?php

namespace Tests\Unit\Infra\External\Aws;

use App\Infra\External\Aws\S3;
use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * S3 アダプタ(Infra/External)の単体テスト。
 *
 * 実 AWS は叩かず、S3Client を注入してモックで各操作を検証する。
 * config() ヘルパ（バケット取得）を使うため Laravel をブートする Tests\TestCase を継承。DBは不要。
 */
final class S3Test extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function makeS3(S3Client $client): S3
    {
        config(['filesystems.disks.s3.bucket' => 'my-bucket']);

        return new S3($client);
    }

    #[Test]
    public function putは内容を保存しパスを返す(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('putObject')
            ->once()
            ->with(['Bucket' => 'my-bucket', 'Key' => 'notes/1.txt', 'Body' => 'hello'])
            ->andReturn(new Result);

        $path = $this->makeS3($client)->put('notes/1.txt', 'hello');

        $this->assertSame('notes/1.txt', $path);
    }

    #[Test]
    public function getは保存済みの内容を返す(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('getObject')
            ->once()
            ->with(['Bucket' => 'my-bucket', 'Key' => 'notes/1.txt'])
            ->andReturn(new Result(['Body' => 'hello']));

        $contents = $this->makeS3($client)->get('notes/1.txt');

        $this->assertSame('hello', $contents);
    }

    #[Test]
    public function existsは存在有無を返す(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('doesObjectExist')
            ->once()
            ->with('my-bucket', 'notes/1.txt')
            ->andReturnTrue();

        $this->assertTrue($this->makeS3($client)->exists('notes/1.txt'));
    }

    #[Test]
    public function deleteはオブジェクトを削除する(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('deleteObject')
            ->once()
            ->with(['Bucket' => 'my-bucket', 'Key' => 'notes/1.txt'])
            ->andReturn(new Result);

        $result = $this->makeS3($client)->delete('notes/1.txt');

        $this->assertNull($result);
    }

    #[Test]
    public function temporary_urlは署名付きurlを返す(): void
    {
        $command = Mockery::mock(CommandInterface::class);

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getUri')->andReturn(new Uri('https://example.com/signed'));

        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('getCommand')
            ->once()
            ->with('GetObject', ['Bucket' => 'my-bucket', 'Key' => 'notes/1.txt'])
            ->andReturn($command);
        $client->shouldReceive('createPresignedRequest')
            ->once()
            ->with($command, '+10 minutes')
            ->andReturn($request);

        $url = $this->makeS3($client)->temporaryUrl('notes/1.txt');

        $this->assertSame('https://example.com/signed', $url);
    }
}
