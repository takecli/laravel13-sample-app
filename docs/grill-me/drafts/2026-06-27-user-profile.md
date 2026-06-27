# ドラフト: ユーザープロフィール（user-profile）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: ユーザープロフィール（slug: `user-profile`、要件定義書 §4.2 / リリース: MVP）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - **依存: `auth`**（§4.1）。初回ログイン時に `users` を `keycloak_sub` で upsert 済み。本機能は認証済みユーザー（`Auth::user()`）が前提。
    - 関連: `article-core`（公開プロフィールの「投稿一覧」が published 記事に依存。§5.2 `users 1─* articles`）。
    - 関連: `gamification`（§4.16・P3。`contribution_points` の**集計ロジック**は P3。本機能は `users.contribution_points` の**保存値の表示**のみ）。
    - 関連: `article-attachments` / `Infra/External/Aws/S3`（§4.9・§6。アバター画像を S3 に置くなら既存 `FileStorageInterface` を流用＝§9 で要確定）。

## 1. 目的（Why）
チームの暗黙知を形式知化するプラットフォーム（§1.1）において、**「誰が書いたか・どれだけ貢献しているか」を可視化**し、投稿文化（§1.3-5 軽いゲーミフィケーション）を支える土台を作る。
- 自分のプロフィール（表示名・アバター・自己紹介）を整えられることで、投稿者の人格・信頼性が可視化される。
- 他ユーザーの公開プロフィールから投稿一覧・貢献度を辿れることで、ナレッジの再利用とオンボーディングを加速する（§1.2）。

## 2. スコープ
- やること（§4.2 / §9 プロフィール行）:
    - 自分のプロフィール**取得**: `GET /me`。
    - 自分のプロフィール**更新**: `PUT /me`（表示名・アバター・自己紹介）。
    - 他ユーザーの**公開プロフィール閲覧**: `GET /users/{user_id}`（投稿一覧・貢献度）。
- やらないこと（今回対象外）:
    - 認証・ログイン・`users` upsert そのもの（→ `auth`／§4.1）。
    - 貢献度ポイントの**集計・バッジ付与ロジック**（→ `gamification`／§4.16・P3）。本機能は保存済みの `contribution_points` を読むだけ。
    - 記事 CRUD・記事の可視性制御本体（→ `article-core`）。公開プロフィールの投稿一覧は article 側の可視性ルールに**従って参照する**だけ。
    - 通知・ブックマーク一覧（別機能。`GET /me/bookmarks`・`GET /me/notifications` は本ドラフト外）。
- 非ゴール（誤解されやすいので明示）:
    - プロフィールに**フォロー / フレンド**等の SNS 機能は持たせない（要件に無い）。
    - メールアドレスや `keycloak_sub` の編集は不可（認証アイデンティティは Keycloak 起源・読み取り専用）。
    - 他人のプロフィールの編集・他人の `contribution_points` 改変は不可。

## 3. ユースケース / ユーザーストーリー
- [ ] US1: 認証済みユーザーは `GET /me` で自分のプロフィール（表示名・アバター・自己紹介・貢献度・メール）を取得できる。
- [ ] US2: 認証済みユーザーは `PUT /me` で自分の表示名・自己紹介・アバターを更新できる（メール / id は不変）。
- [ ] US3: ユーザーは `GET /users/{user_id}` で他ユーザーの**公開**プロフィール（表示名・アバター・自己紹介・貢献度・公開済み投稿一覧）を閲覧できる。
- [ ] US4: 存在しない / 退会済みユーザーの `GET /users/{user_id}` は 404 を返す。
- [ ] US5（関連 US-G1）: 貢献度ポイントが自分・他者のプロフィールに表示される（値は §4.16 が更新、本機能は表示）。

## 4. データモデル設計
- エンティティ: `users`（§5.1。**既存テーブルを正本のスキーマへ作り直す**前提。§9 に移行論点あり）。
- 属性 / 型 / 制約（§5.1 users 行 ＋ §5 共通方針）:

  | 列 | 型 | 制約 / 備考 |
  |---|---|---|
  | `id` | `CHAR(36)` UUID | PK（§5 共通方針） |
  | `keycloak_sub` | string | **unique**。Keycloak 起源、読み取り専用。名寄せキー（§4.1） |
  | `email` | string | Keycloak 起源、本機能では読み取り専用（編集不可） |
  | `display_name` | string | 編集可。必須・最大長は §9 で確定 |
  | `avatar_url` | string(null可) | 編集可。アバター画像の URL or S3 キー（§9 で確定） |
  | `bio` | text(null可) | 編集可。自己紹介。最大長は §9 で確定 |
  | `contribution_points` | int | 既定 0。**本機能は読み取りのみ**（更新は `gamification`） |
  | `created_at` / `updated_at` | timestamp | 監査列（§5 共通方針） |
  | 監査ユーザー列 / `deleted_at` | — | §5 共通方針に準拠（論理削除対象なら `deleted_at`。退会フローは §9） |

  > 注: 現状の `App\Models\User` は `keycloak_id` / `name` / `email` のみ（Reliese 生成）。正本 §5 の `keycloak_sub` / `display_name` / `avatar_url` / `bio` / `contribution_points` へ作り直す必要がある（§9）。
- リレーション（§5.2）:
    - `users 1─* articles`（author）… 公開プロフィールの投稿一覧の源。
    - `users 1─* team_members`、`users 1─* notifications / user_badges`（本機能では参照しない or 投稿一覧に限定）。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須（§9）。`ApiResponse` エンベロープ（`data/message/result`）準拠（§6 可観測性）。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/me` | 自分のプロフィール取得 | （body なし。認証セッション） | `{ id, display_name, avatar_url, bio, email, contribution_points }` | 401（未認証） |
| PUT | `/me` | 自分のプロフィール更新 | `display_name`(必須), `bio`(任意), `avatar`/`avatar_url`(任意・形式は §9) | 更新後プロフィール | 401 / 422（検証） / 500 |
| GET | `/users/{user_id}` | 他ユーザーの公開プロフィール | path: `user_id` | `{ id, display_name, avatar_url, bio, contribution_points, articles[] }`（公開済み投稿のみ） | 401 / 404（存在しない・退会） |

- 共通: 失敗時は `ApiResponse` の `badRequest`/`unauthenticate`/`notFound`/`serverError` を返す（§6 / php.md）。
- `GET /users/{user_id}` の `articles[]`: その人の **published かつ閲覧者が見られる可視性**の記事に限定（§8 可視性ルールに従う。具体の絞り込みは article-core 実装に委譲、§9 で境界確定）。
- レスポンスのキー名（snake_case / camelCase）と日時 ISO 8601 化は既存 `UserResource` / Resource 規約に合わせる。

## 6. UI / 画面設計
- 画面 / 状態:
    - **自分のプロフィール編集画面**（`routes/_authenticated/` 配下）: 表示名・アバター・自己紹介の表示と編集フォーム。
    - **公開プロフィール画面** `/users/{user_id}`: 表示名・アバター・自己紹介・貢献度・投稿一覧（カード）。
    - 状態: ローディング / 取得成功 / 404（存在しない）/ 認証エラー（401 → ログイン導線）。
- 主要操作:
    - プロフィール取得（`ProfileAPI.getMe()` / `ProfileAPI.getUser(id)`、`api/profile.ts` に集約）。
    - 編集保存（`ProfileAPI.updateMe(input)`、`client` 経由・CSRF 自動付与＝書き込み系）。
    - 公開プロフィールから各投稿（記事詳細）への遷移。
- 入力バリデーション・エラー表示:
    - `display_name` 必須・最大長（§9 で確定）。`bio` 最大長。アバターは MIME / サイズ（§6 既定 10MB、§9 で確定）。
    - サーバ 422 を捕捉し（`ApiError`）フィールド単位でエラー表示。握り潰さない（typescript.md）。
- UI 規約: Chakra UI v3 ＋ `components/ui/` ラッパ、`api/types.ts` に DTO 型集約（typescript.md）。

## 7. 技術選定と根拠
- 採用:
    - 既存 `Team` 縦切り（§6 アーキテクチャ）を踏襲したレイヤード構成（Input/Output/UseCase/Filter or 直参照/Repository interface+impl/Controller/Request/Resource）。
    - 認証は Keycloak セッション（BFF）。`Auth::user()` で本人特定（§4.1 / §13）。
    - 文言は `lang/en/*.php` 翻訳キー、レスポンスは `ApiResponse`（§6）。
- 理由:
    - 正本 §6 が `Team` 縦切りの全機能踏襲を非機能要件として明記。新規ドメインも同型にすることで依存方向（`Http → Applications → Domains`、Eloquent は Infra のみ）を守れる。
- 却下した代替案 / 却下理由:
    - プロフィール更新を `users` Eloquent へ Controller から直接保存 → 依存方向違反（§6 / php.md「UseCase/Controller から `App\Models` を直接使わない」）。Repository interface 経由にする。
    - アバターを base64 で `avatar_url` に直保存 → 肥大化・配信非効率。S3（`FileStorageInterface`）＋署名付き URL が正本方針（§6 ファイル）。採否は §9。
    - メール / `keycloak_sub` を編集可能にする → 認証アイデンティティ破壊。Keycloak のみ（§13）なので読み取り専用に固定。

## 8. エッジケース・非機能
- エッジケース:
    - `GET /users/{user_id}` で存在しない / 退会済み ID → 404。
    - `PUT /me` で `display_name` 空 / 長すぎ → 422。`bio` 超過 → 422。アバター MIME 不正・サイズ超過 → 422。
    - 公開プロフィールの投稿一覧が 0 件 → 空配列で 200。
    - 投稿一覧に `draft/in_review/archived` や閲覧権限の無い `team` 記事を**混入させない**（§8・§7.1）。
- 認可 / 権限（§8）:
    - `/me`・`PUT /me`: 認証済み本人のみ（`api/*` は Keycloak セッション必須・§6 認可）。他人の編集不可。
    - `GET /users/{user_id}`: 認証済みユーザーが閲覧可。表示する投稿は閲覧者の権限に応じて可視性フィルタ（§8 の `published`＋`team`/`public` 行）。
    - `email` は本人の `GET /me` のみ返し、`GET /users/{id}` では返さない（個人情報の出し分け）→ §9 で最終確定。
- 失敗時の挙動: 例外は Controller で `try/catch` → `Log::error(__('messages.error', ...))` ＋ `report($e)` ＋ `ApiResponse::serverError()`（§6 可観測性 / php.md 定型）。
- 性能・可用性で意識する点:
    - 投稿一覧はページング（§6 性能・既定 `Pagination`）。Infra で必要列を eager load し N+1 回避。
    - アバターは S3 ＋署名付き URL（採用時）。重い貢献度集計は本機能では行わない（保存値読むだけ）。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules（中身は転記しない・参照のみ）:
    - バックエンド: `.claude/rules/php.md`（PHPUnit 12 / Unit・Feature / 行カバレッジ 100% / Mockery / `#[Test]` / AAA）。
    - フロントエンド: `.claude/rules/typescript.md`（型エラーなし・ビルド可・`client` 経由・`ApiError` 捕捉）。
    - 全体: `.claude/rules/general.md`（Definition of Done）、§12 受入基準。
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - UseCase: ユーザー用 `RepositoryInterface` を Mockery でモック。更新失敗・取得失敗の例外分岐は `andThrow` で到達。
        - Controller catch: `$this->app->instance(Interface::class, $throwingMock)`、本人特定は `Auth::shouldReceive(...)`。
        - S3 採用時はアバター保存を `FileStorageInterface` モックで差し替え（実 S3 に触れない。LocalStack は統合のみ）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US2: `email`/`keycloak_sub`/`id` が更新されない不変条件、`display_name` 必須・長さ境界（422）。
        - US3/US4: 公開プロフィールの投稿一覧が**可視性フィルタ後**のみであること、404 経路。
        - US1 と US3 の**レスポンス差分**（`email` の出し分け）。
    - DB / 外部サービス依存で注意する点:
        - Feature テストは MySQL テスト DB（`learning_portal_testing` / `.env.testing`）前提。sqlite 不可（生 SQL マイグレーション）。
        - 投稿一覧テストは `articles` の status/visibility シードが必要（article-core 連携）。

## 9. 未確定（brainstorming で詰める）
- [ ] **既存 `users` テーブルの作り直し**: 現状 `keycloak_id/name/email` → 正本 §5 の `keycloak_sub/display_name/avatar_url/bio/contribution_points` へ移行する範囲・タイミング（`auth` 機能と本機能のどちらが列追加を担うか）。
- [ ] **アバターの実体**: `avatar_url` は (a) クライアントが渡す外部 URL 文字列か、(b) `PUT /me` に multipart で画像をアップロードし S3（`FileStorageInterface`）へ保存して署名付き URL を返すか。アップロードなら別エンドポイントが要るか（§9 API には `PUT /me` のみ）。MIME ホワイトリスト・サイズ上限（§6 既定 10MB を流用するか）。
- [ ] **`display_name` / `bio` の制約**: 必須/任意・最大長・許可文字・トリミング・XSS（bio に Markdown を許すか、許すならサニタイズ方針＝§6 セキュリティ）。
- [ ] **`GET /users/{id}` のレスポンス項目**: `email` を含めない方針で確定してよいか。`team` 所属やバッジ（§4.16）まで含めるか（MVP は貢献度＋投稿一覧に限定する想定）。
- [ ] **投稿一覧の責務境界**: 公開プロフィールの `articles[]` を本機能で組むか、article-core の一覧 UseCase（著者フィルタ）を呼ぶか。ページングのパラメータ（`page/limit/sort`）と可視性判定の置き場所。
- [ ] **退会 / 論理削除**: `users` に `deleted_at`（退会）を持たせるか。退会ユーザーの `GET /users/{id}` は 404 か、ツームストーン表示か。投稿の著者表示の扱い。
- [ ] **`contribution_points` の初期化**: gamification(P3) 未実装の MVP 段階で常に 0 を返す扱いで良いか（UI 表示の有無）。
- [ ] **`user_id` の指定形式**: `GET /users/{user_id}` の `{user_id}` は UUID 直か、別途公開 slug/handle を持たせるか（§5 には handle 列なし → UUID 直の想定）。
