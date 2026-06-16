# CLAUDE.md

Claude Code がこのリポジトリで作業するためのガイド。**実装前に必ず一読すること。**

## プロジェクト概要

学習ポータルのサンプルアプリ。バックエンドは **レイヤード/クリーンアーキテクチャ** を採用した Laravel API、フロントは React SPA。

- **Backend**: PHP `^8.3` / Laravel `^13.8` / PHPUnit `^12.5` / Pint / Mockery
- **Frontend**: React `19` / TypeScript `6` / TanStack Router / Chakra UI `v3` / Tailwind `4` / Vite `8`
- **認証**: Keycloak（Laravel Socialite, セッション/BFF 方式）
- **DB**: MySQL 8（開発 `learning_portal` / テスト `learning_portal_testing`）
- **実行環境**: Docker Compose（`app` / `web`=nginx:7888 / `mysql`:6306 / `tbls`）。PHP コマンドは原則コンテナ内で実行する。

詳細な設計仕様は `docs/superpowers/specs/` を参照。

## コーディング規約（`.claude/rules/`）

作業前に該当する規約を読むこと。ファイル種別ごとに対象が分かれている。

| ファイル | 対象 | 内容 |
|---|---|---|
| `.claude/rules/general.md` | **常時** | 言語方針・ワークフロー・完了条件・禁止事項 |
| `.claude/rules/php.md` | `*.php` | Laravel / クリーンアーキテクチャ・テスト規約 |
| `.claude/rules/typescript.md` | `*.ts` / `*.tsx` | React / TanStack Router / Chakra・API 規約 |

→ **`.php` を編集するなら `php.md`、`.ts`/`.tsx` を編集するなら `typescript.md` を必ず参照する。**

---

## アーキテクチャ（最重要）

`app/` は **依存方向が一方向** のレイヤードアーキテクチャ。`Team` 機能が全レイヤーを通した**リファレンス実装（縦切り）**なので、新機能はこれを真似る。

```
app/
├── Http/                     ← 入口（フレームワーク依存）
│   ├── Controllers/          薄く保つ: validate → UseCase → Resource → ApiResponse
│   ├── Requests/             FormRequest（バリデーション）
│   ├── Resources/            JsonResource（出力整形）
│   └── Reponses/             ApiResponse（共通レスポンス。※綴りは既存に合わせる）
├── Applications/             ← ユースケース層（フレームワーク非依存）
│   ├── UseCase/              アプリのオーケストレーション
│   ├── Input/                ユースケース入力 DTO
│   └── Output/               ユースケース出力 DTO
├── Domains/                  ← ドメイン層（純粋 PHP。Laravel/DB に依存しない）
│   ├── Models/               エンティティ・値オブジェクト（Filter/Result 含む）
│   ├── Enums/                バック付き enum
│   └── Repositories/         リポジトリ "インターフェース"（ポート）
├── Infra/                    ← インフラ層（ドメインのポート実装＝アダプタ）
│   └── Persistence/          Eloquent を使う Repository 実装
├── Models/                   ← Eloquent モデル（Reliese 生成。Infra からのみ触る）
├── Constants/                定数（例: Pagination）
└── Providers/                DI バインド（interface → impl）
```

**依存ルール（厳守）**
- `Http` → `Applications` → `Domains` の向きにのみ依存する。
- `Domains` は **Laravel にも Eloquent にも依存しない**（`use Illuminate\...` を書かない）。
- Eloquent (`App\Models`) に触れてよいのは `Infra` 層だけ。UseCase/Controller から `App\Models` を直接使わない。
- リポジトリは **インターフェースを `Domains/Repositories` に置き、実装を `Infra/Persistence` に置く**。バインドは `AppServiceProvider::register()`：
  ```php
  $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
  ```

### リクエストの流れ（チーム一覧の例）

```
GET /api/v1/teams
  → routes/web_api.php           （prefix: api/v{version}）
  → ListTeamRequest              （バリデーション）
  → TeamController::listTeam      （try/catch のみ。ロジックは持たない）
      → ListTeamInput            （Application DTO に詰め替え）
      → ListTeamUseCase::execute → ListTeamSearch（Domain Filter）に変換
          → TeamRepositoryInterface::listTeam  （Infra 実装が Eloquent → Domain 変換）
          → ListTeamResult       （Domain）
      → ListTeamOutput           （Application DTO）
  → TeamsResource                （配列へ整形）
  → ApiResponse::success         （{ data, message, result } エンベロープ）
```

ルートは `bootstrap/app.php` で `web_api.php` を catch-all の `web.php` より**先に**登録。`api/*` は例外時に JSON を返す設定。

---

## PHP 実装規約

- **`final class`** を基本とする（継承前提の基底クラスを除く）。
- **コンストラクタプロモーション + `readonly`** で DTO/値オブジェクトを定義する。
- **型を必ず付ける**（プロパティ・引数・戻り値）。`mixed` は最小限。
- インターフェース実装メソッドには **`#[Override]`** を付ける。
- enum は **バック付き**（`enum X: string`）。マスタ値は enum で表現する。
- 例外処理は **コントローラの責務**。`try/catch (Exception $e)` で `Log::error(__('messages.error', ...))` + `report($e)` の後に `ApiResponse::serverError()` を返す（`AuthController`/`KeycloakController` がパターン）。**`dd()`/`var_dump()` を残さない。**
- ユーザー向け文言は `lang/en/*.php` の翻訳キー（`__('messages.success', [...])`）を使う。
- 整形は **Pint**。コミット前に `task php:pint` を通す。

---

## テスト（カバレッジ 100% を維持）

PHPUnit 12。`Unit` / `Feature` の 2 スイート（`phpunit.xml`）。**行カバレッジ**を計測対象とし、**100% を維持する**。

**実行コマンド（Docker 経由）**
```bash
task php:coverage-text     # カバレッジ付きでテスト実行（ターミナル出力）
task php:coverage-html     # HTML レポート → coverage/
docker compose exec app ./vendor/bin/phpunit --filter Foo   # 個別実行
```

**テストの書き分け**
| 種別 | 継承元 | DB | 用途 |
|---|---|---|---|
| 純粋単体 | `PHPUnit\Framework\TestCase` | 不要 | DTO/Resource/Request/Domain/UseCase（依存はモック） |
| アプリ依存 | `Tests\TestCase` | 不要 | `response()` 等のヘルパを使う（例: `ApiResponse`） |
| 統合/Feature | `Tests\TestCase` + `RefreshDatabase` | 要 | Controller・Repository（実ルート/実 DB） |

**規約とテクニック**
- メソッド名は日本語可（`test_...` または `#[Test]` 属性）。意図が伝わる命名にする。
- 依存差し替えは **`Mockery`** + `MockeryPHPUnitIntegration`。例外分岐は依存をモックで `andThrow` して到達させる。
  - UseCase: `TeamRepositoryInterface` をモック。
  - Controller の catch: `$this->app->instance(Interface::class, $throwingMock)`、ファサードは `Auth::shouldReceive(...)` / `Socialite::shouldReceive(...)`。
- AAA（Arrange / Act / Assert）で書く。`ListTeamUseCaseTest` を雛形にする。

**⚠ DB に関する罠**
- マイグレーションは **MySQL 専用の生 SQL**（`UUID_TO_BIN` / `ENUM` / `COMMENT`）。`sqlite :memory:` では動かない。Feature テストは **MySQL のテスト DB（`learning_portal_testing`, `.env.testing`）** を前提とする。

---

## フロントエンド（resources/js）

- **API クライアント**: `api/client.ts` の `client` を必ず経由する。`baseURL=/api/v1`、`credentials: include`（セッション Cookie）、書き込み系は `meta[name=csrf-token]` を付与。`ApiResponse` のエンベロープ（`data/message/result`）を展開して `data` を返し、失敗時は `ApiError` を投げる。機能ごとに `api/<feature>.ts`（例: `AuthAPI`）を追加する。
- **ルーティング**: TanStack Router のファイルベース。`routes/` に配置し、`routeTree.gen.ts` は**自動生成**なので手で編集しない（`vite` が再生成）。
- **UI**: Chakra UI v3 + 自前ラッパ（`components/ui/`）。テーマは `theme.ts`、カラーモードは `next-themes`。
- **型**: API の入出力型は `api/types.ts` に集約する。
- フォーマッタ/リンタは未設定。**周囲のファイルの流儀に合わせる**（`api/` 層はダブルクォート・インデント 4・セミコロンなし）。

---

## よく使うコマンド

```bash
# セットアップ（コンテナ起動後）
docker compose up -d app mysql web

# マイグレーション
task migrate:apply        # migrate
task migrate:fresh        # migrate:fresh
task migrate:seed         # db:seed

# 品質ゲート（コミット前に通す）
task php:pint             # Pint で整形
task php:coverage-text    # テスト + カバレッジ

# フロント
npm run dev               # Vite 開発サーバ
npm run build             # ビルド
```

---

## 既知の注意点

- `app/Http/Reponses/`（"Responses" の綴り違い）と `getUser(... $vesrion)` / `redirect('/dashbord')` 等のタイポは**既存コードに合わせる**（勝手にリネームしない。影響範囲が広い）。
- `app/Domains/Enums/Notes/Status.php` は名前空間が `App\Domain\Enum\Notes` でパスと **PSR-4 不一致**（オートロード不可の既存バグ）。`Notes` 機能を実装する際は名前空間修正が必要。
- `App\Models` は Reliese 生成物。スキーマ変更時はマイグレーションと整合させる。

## 変更時の鉄則

1. **新機能は `Team` の縦切りを踏襲**（Input/Output/UseCase/Filter/Result/Repository interface+impl/Controller/Request/Resource）。
2. **依存方向を破らない**（Domain に Laravel を持ち込まない／UseCase から Eloquent を触らない）。
3. **テストを書き、カバレッジ 100% を維持**してから完了とする。`task php:coverage-text` の出力（行 100%）を確認してからでないと「完了」と言わない。
4. **コミット前に `task php:pint`**。
5. 「動いた」「直った」は**コマンド出力で裏取りしてから**報告する。
