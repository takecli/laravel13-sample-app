<?php

namespace App\Domains\Services;

/**
 * ファイル/オブジェクトストレージのポート（能力で定義、ベンダー非依存）。
 *
 * Application/Domain はこのインターフェースにのみ依存し、実体（S3 等）は
 * Infra/External 層のアダプタが実装する。AWS 等の SDK 用語をここに持ち込まない。
 */
interface FileStorageInterface
{
    /**
     * 内容を保存し、保存先のパス（キー）を返す。
     *
     * @param  string  $path
     * @param  string  $contents
     */
    public function put(string $path, string $contents): string;

    /**
     * 保存済みの内容を取得する。
     *
     * @param  string  $path
     */
    public function get(string $path): string;

    /**
     * 指定パスが存在するか。
     *
     * @param  string  $path
     */
    public function exists(string $path): bool;

    /**
     * 指定パスを削除する。
     *
     * @param  string  $path
     */
    public function delete(string $path): void;

    /**
     * 一時的にアクセス可能な署名付き URL を返す。
     *
     * @param  int  $minutes  有効期限（分）
     * @param  string  $path
     */
    public function temporaryUrl(string $path, int $minutes = 10): string;
}
