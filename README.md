# laravel-13-sample-app

学習ポータルのサンプルアプリ。バックエンドは **レイヤード/クリーンアーキテクチャ** を採用した Laravel API、フロントは React SPA。

## 技術スタック

- **Backend**: PHP `^8.3` / Laravel `^13.8` / PHPUnit `^12.5` / Pint / Mockery
- **Frontend**: React `19` / TypeScript `6` / TanStack Router / Chakra UI `v3` / Tailwind `4` / Vite `8`
- **認証**: Keycloak（Laravel Socialite, セッション/BFF 方式）
- **DB**: MySQL 8（開発 `learning_portal` / テスト `learning_portal_testing`）
- **ストレージ**: S3（開発は LocalStack でエミュレート）
- **実行環境**: Docker Compose（`app` / `web`=nginx:7888 / `mysql`:6306 / `tbls` / `localstack`:4566）

## アーキテクチャ

`app/` は依存方向が一方向（`Http → Applications → Domains`）のレイヤード構成。`Team` 機能が全レイヤーを通したリファレンス実装（縦切り）です。

```
app/
├── Http/          入口（Controllers / Requests / Resources / Reponses）
├── Applications/  ユースケース層（UseCase / Input / Output DTO）
├── Domains/       ドメイン層（Models / Enums / Repositories(IF) / Services(IF)）
├── Infra/         インフラ層（Persistence=Eloquent実装 / External=外部SDK実装）
├── Models/        Eloquent モデル（Reliese 生成。Infra からのみ触る）
└── Providers/     DI バインド（interface → impl）
```

詳細な開発ガイド・規約は **[CLAUDE.md](./CLAUDE.md)** と `.claude/rules/`、設計仕様は `docs/superpowers/` を参照。

## セットアップ

```bash
# コンテナ起動
docker compose up -d app mysql web localstack

# 依存インストール・アプリキー・マイグレーション
docker compose exec app composer install
docker compose exec app php artisan key:generate
task migrate:apply
task migrate:seed

# フロント
npm install
npm run dev
```

`.env` は `.env.example` を複製して設定する（DB / Keycloak / AWS など）。
※ `.env` 等のシークレットは Git 管理外（`.gitignore`）。

## 主な API（`/api/v{version}`）

| メソッド | パス | 概要 |
|---|---|---|
| GET | `/api/v1/auth` | 認証ユーザー情報取得 |
| GET | `/api/v1/auth/keycloak/redirect` | Keycloak 認可リダイレクト |
| GET | `/api/v1/auth/keycloak/callback` | Keycloak コールバック |
| GET | `/api/v1/teams` | チーム一覧（`public_status` 等で絞り込み） |
| POST | `/api/v1/teams` | チーム作成 |
| PUT | `/api/v1/teams/{team_id}` | チーム更新 |

レスポンスは共通エンベロープ `{ data, message, result }`。`teams` は認証必須（未認証は 401 JSON）。

## よく使うコマンド

```bash
# 品質ゲート（コミット前に通す）
task php:pint              # Pint で整形
task php:coverage-text     # テスト + カバレッジ（行 100% を維持）

# マイグレーション
task migrate:apply         # migrate
task migrate:fresh         # migrate:fresh
task migrate:seed          # db:seed

# 生成（artisan make ラッパ）
task php:make -- model Team -m
task php:make-table -- create_foos_table FooSeeder   # migration + seeder をセット生成

# フロント
npm run dev                # Vite 開発サーバ
npm run build              # ビルド
```

## テスト

PHPUnit 12（`Unit` / `Feature` の 2 スイート）。**行カバレッジ 100% を維持**します。

```bash
task php:coverage-text                                   # 全体 + カバレッジ
docker compose exec app ./vendor/bin/phpunit --filter Foo  # 個別
```

> マイグレーションは MySQL 専用の生 SQL（`UUID_TO_BIN` / `ENUM` / `COMMENT`）のため、Feature テストは MySQL のテスト DB（`learning_portal_testing` / `.env.testing`）を前提とします（sqlite では動きません）。

## ストレージ（LocalStack）

S3 は開発時 LocalStack でエミュレートします。`docker compose up -d localstack` で起動し、`docker/localstack/ready.d/init-aws.sh` が起動時にバケットを自動作成します。アプリは `Domains/Services/FileStorageInterface`（ポート）経由で利用し、実体は `Infra/External/Aws/S3` が実装します。

## ライセンス

本リポジトリはサンプル用途です。Laravel フレームワーク本体は [MIT ライセンス](https://opensource.org/licenses/MIT)。
