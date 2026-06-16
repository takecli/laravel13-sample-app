---
description: PHP / Laravel コーディング規約（レイヤード/クリーンアーキテクチャ）
globs:
  - "**/*.php"
alwaysApply: false
---

# PHP / Laravel コーディング規約

対象: `*.php`。共通規約は `general.md`、全体像は ルート `CLAUDE.md` を前提とする。

## 言語・整形

- **PHP `^8.3` / Laravel `^13.8`。**
- 整形は **Pint**（`task php:pint`）。手で独自整形しない。インデントはスペース 4。
- `declare(strict_types=1)` は**既存に倣い付けない**（現状コードベースで未使用のため統一を優先）。
- 型は**必ず明示**する（プロパティ・引数・戻り値）。`mixed` は本当に必要な箇所のみ。

## クラス設計

- アプリ層のクラスは原則 **`final`**（継承を意図する基底クラスを除く）。
- DTO・値オブジェクトは**コンストラクタプロモーション + `readonly`** で不変にする。

  ```php
  final class ListTeamInput
  {
      public function __construct(
          public readonly ?string $userId = null,
          public readonly int $page = Pagination::PAGE_DEFAULT,
      ) {}
  }
  ```

- インターフェース実装メソッドには **`#[Override]`** を付ける。
- enum は**バック付き**（`enum PublicStatus: string`）。マスタ・区分値は enum で表現し、生文字列を散らさない。

## 命名

| 対象 | 規則 | 例 |
|---|---|---|
| クラス | PascalCase | `ListTeamUseCase` |
| メソッド・変数 | camelCase | `listTeam`, `$teamRepo` |
| 定数 | UPPER_SNAKE_CASE | `LIMIT_DEFAULT` |
| インターフェース | `〜Interface` | `TeamRepositoryInterface` |
| 入出力 DTO | `〜Input` / `〜Output` | `ListTeamOutput` |
| ドメイン検索条件 | `〜Search`（Filter） | `ListTeamSearch` |
| ドメイン結果 | `〜Result` | `ListTeamResult` |
| ユースケース | `〜UseCase` | `ListTeamUseCase` |

## レイヤー別ルール（依存方向を厳守）

`Http → Applications → Domains`。逆流・横断は禁止。

- **Domains/**: 純粋 PHP。`use Illuminate\...` も Eloquent も**書かない**。エンティティ／値オブジェクト／enum／リポジトリ**インターフェース**のみ。
- **Applications/**（UseCase）: Input を受け取り、Domain の Filter に詰め替え、リポジトリ**インターフェース**経由でドメインを操作し、Output を返す。Eloquent を直接触らない。
- **Infra/Persistence/**: リポジトリ**実装**。ここだけが `App\Models`（Eloquent）に触れる。`#[Override]` を付け、`modelToDomain()` で Eloquent → ドメインエンティティへ変換する。
- **Http/Controllers/**: **薄く**保つ。`validate → UseCase → Resource → ApiResponse` のみ。ビジネスロジックを持たない。
- **Http/Requests/**: バリデーションは FormRequest に集約。
- **Http/Resources/**: 出力整形は JsonResource。
- **App\Models/**: Reliese 生成の Eloquent。`HasUuids` / `$casts` / `$fillable` を定義。Infra 以外から使わない。

DI バインドは `AppServiceProvider::register()` に集約：

```php
$this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
```

## コントローラの例外処理（定型）

ロジックを `try`、失敗を `catch (Exception $e)` で受け、**ログ＋report＋共通エラーレスポンス**を返す。`dd()` は厳禁。

```php
try {
    // validate → UseCase → Resource
    return ApiResponse::success(__('messages.success', ['action' => 'Get', 'Teams']), $resource);
} catch (Exception $e) {
    Log::error(__('messages.error', ['action' => 'Get', 'resource' => 'Teams']));
    report($e);

    return ApiResponse::serverError();
}
```

- レスポンスは必ず `App\Http\Reponses\ApiResponse`（`success`/`badRequest`/`unauthenticate`/`forbidden`/`notFound`/`serverError`）経由。`{ data, message, result }` エンベロープを守る。
- ユーザー向け文言は `lang/en/*.php` の翻訳キー（`__('messages.*')`）。文字列直書きしない。

## テスト（カバレッジ 100% を維持）

PHPUnit 12。`Unit`／`Feature` の 2 スイート。**行カバレッジ 100%** を前提に新規コードへ必ずテストを足す。

- **継承元の使い分け**
  - 純粋単体（DTO・Resource・Request・Domain・UseCase）→ `PHPUnit\Framework\TestCase`、依存は Mockery。
  - `response()` 等ヘルパ利用（例: `ApiResponse`）→ `Tests\TestCase`（DB 不要）。
  - 実ルート・実 DB（Controller・Repository）→ `Tests\TestCase` + `RefreshDatabase`。
- **テストメソッドは `#[Test]` 属性を付け、`test` プレフィックスは使わない**（`use PHPUnit\Framework\Attributes\Test;` を import）。メソッド名は日本語可で「何を検証するか」を表す。AAA（Arrange/Act/Assert）で書く。`ListTeamUseCaseTest` を雛形に。

  ```php
  use PHPUnit\Framework\Attributes\Test;

  #[Test]
  public function 正常系_チーム一覧を200で返す(): void
  {
      // Arrange → Act → Assert
  }
  ```
- **例外分岐の到達**は依存を `andThrow` させる：
  - UseCase: `Mockery::mock(TeamRepositoryInterface::class)`。
  - Controller catch: `$this->app->instance(Interface::class, $throwingMock)`、ファサードは `Auth::shouldReceive(...)` / `Socialite::shouldReceive(...)`。
- **⚠ DB の罠**: マイグレーションは MySQL 専用の生 SQL（`UUID_TO_BIN`/`ENUM`/`COMMENT`）。sqlite では動かない。Feature テストは MySQL テスト DB（`learning_portal_testing` / `.env.testing`）前提。
- 実行: `task php:coverage-text`（全体＋カバレッジ）、`docker compose exec app ./vendor/bin/phpunit --filter Foo`（個別）。

## やってはいけない

- UseCase / Controller から `App\Models`（Eloquent）を直接参照する。
- Domain 層に Laravel / Eloquent を持ち込む。
- リポジトリをインターフェース無しで具象に直結する。
- コントローラにビジネスロジックを書く。
- テストメソッドに `test` プレフィックスを付ける（必ず `#[Test]` 属性を使う）。
- `dd()` / `var_dump()` を残す。
