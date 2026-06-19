<?php

namespace App\Infra\External\Aws;

use App\Domains\Services\FileStorageInterface;
use Aws\Laravel\AwsFacade;
use Aws\S3\S3Client;
use Override;

final class S3 implements FileStorageInterface
{
    private S3Client $client;

    private string $bucket;

    public function __construct(?S3Client $client = null)
    {
        // テスト時は S3Client を注入する。未指定なら AWS 設定から生成。
        $this->client = $client ?? AwsFacade::createClient('s3');
        $this->bucket = (string) config('filesystems.disks.s3.bucket');
    }

    #[Override]
    public function put(string $path, string $contents): string
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => $contents,
        ]);

        return $path;
    }

    #[Override]
    public function get(string $path): string
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        return (string) $result['Body'];
    }

    #[Override]
    public function exists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }

    #[Override]
    public function delete(string $path): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);
    }

    #[Override]
    public function temporaryUrl(string $path, int $minutes = 10): string
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $request = $this->client->createPresignedRequest($command, "+{$minutes} minutes");

        return (string) $request->getUri();
    }
}
