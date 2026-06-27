# ドラフト: 貢献度 / バッジ（gamification）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 貢献度 / バッジ（slug: `gamification`、要件定義書 §4.16 / リリース: P3）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - **依存: `article-reactions`**（§4.11・P2）。被リアクション数（`reactions` の `like/helpful/insightful`）が貢献度ポイントの主要な集計入力。リアクション付与/解除イベントが集計トリガの候補（§9）。
    - **依存: `article-core`**（§4.6・MVP）。投稿数（`published` 記事数）が集計入力。記事公開イベントが集計トリガの候補。
    - **依存: `user-profile`**（§4.2・MVP）。本機能が更新する `users.contribution_points` と付与した `user_badges` を**表示する側**。プロフィール上の貢献度/バッジ表示は user-profile が描画し、本機能は値の**生成・更新**を担う（重複させない）。
    - 関連: `notifications`（§4.15・P3）。バッジ付与を通知する場合の連携（`Notification\Type` に専用種別は未定義 → §9）。
    - 横断利用: §6 非同期（Laravel Queue / 既存 `jobs`）。重い集計は Queue へ。

## 1. 目的（Why）
チームの暗黙知を「書く・読む・育てる」サイクル（§1.1）で形式知化するにあたり、**投稿・被リアクションを貢献度として可視化**し、軽いゲーミフィケーション（§1.3-5）で投稿文化を醸成する。
- 投稿数・被リアクション等から貢献度ポイントを集計し、`users.contribution_points` に反映する（US-G1）。
- 一定条件でバッジ（`user_badges`）を付与し、プロフィールで実績として表示する。
- 「書くと反応が返り、可視化される」体験で Contributor の継続投稿を促す（§1.2 の課題解決）。

## 2. スコープ
- やること（§4.16 / §5 `users.contribution_points`・`user_badges` 行 / US-G1）:
    - 投稿数・被リアクション等を入力とした**貢献度ポイントの集計**と `users.contribution_points` への反映。
    - 条件成立時の**バッジ付与**（`user_badges` への `badge_key` 追加）。
    - 集計・付与は **Laravel Queue（既存 `jobs`）** で非同期実行（§6 可用性/非同期。重い集計は Queue）。
    - （表示は user-profile に委譲しつつ）本機能が公開する API 露出範囲は §9 で確定。最低限、自分の貢献度内訳・保有バッジ一覧の取得を候補とする。
- やらないこと（今回対象外）:
    - プロフィール画面そのものの描画・`GET /me`・`GET /users/{id}`（→ `user-profile`／§4.2）。本機能は値を作るだけ。
    - リアクション付与/解除・記事 CRUD 本体（→ `article-reactions`／`article-core`）。本機能はそれらのイベント/集計結果を読む。
    - 通知配信本体（→ `notifications`）。バッジ付与通知を出す場合も配信は notifications に委譲。
    - ランキング/リーダーボード、ポイント消費・交換（要件に無い）。
- 非ゴール（誤解されやすいので明示・§13 準拠）:
    - AI による貢献度評価・自動要約/自動タグ連動（§13 AI は対象外）。
    - 課金・ポイント換金（§13 課金は非ゴール）。
    - リアルタイムなポイント反映の保証（非同期集計が前提。即時整合は求めない・§6 非同期）。
    - 他者の `contribution_points` / `user_badges` を手動編集する管理 UI（本機能の自動付与のみ）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1（US-G1）: 記事が `published` になる / 自分の記事に被リアクションが付くと、対象ユーザーの `contribution_points` が（非同期で）加算される。
- [ ] US2: リアクションが**解除**された / 記事が `archived`・削除されたとき、ポイントが過剰に残らない（再集計 or 減算で整合する。方式は §9）。
- [ ] US3: 貢献度が一定条件（しきい値/イベント）を満たすと、対応する `badge_key` のバッジが付与される（同一バッジは重複付与しない）。
- [ ] US4: ユーザーは自分の貢献度（内訳）と保有バッジ一覧を確認できる（API 露出範囲は §9）。
- [ ] US5: 他ユーザーのプロフィールに、その人の `contribution_points` と公開バッジが表示される（描画は user-profile、値は本機能が用意）。
- [ ] US6（任意・§9）: バッジ付与時に本人へ通知が届く（notifications 連携が確定すれば）。

## 4. データモデル設計
- エンティティ:
    - `users.contribution_points`（§5.1 users 行）… 本機能が**書き込む**集計値（user-profile は読むだけ）。
    - `user_badges`（§5.1）… 付与済みバッジ。`badge_key` は enum（§5「`badge_key`: enum」）。
- 属性 / 型 / 制約（§5 共通方針: `id` は `CHAR(36)` UUID、監査列、論理削除対象は `deleted_at`）:

  | テーブル.列 | 型 | 制約 / 備考 |
  |---|---|---|
  | `users.contribution_points` | int | 既定 0。本機能が集計で更新。負値にしない（下限 0 を保証・§9 の再集計方式に依存） |
  | `user_badges.id` | `CHAR(36)` UUID | PK（§5 共通方針） |
  | `user_badges.user_id` | `CHAR(36)` UUID | FK → `users.id`（§5.2 `users 1─* user_badges`） |
  | `user_badges.badge_key` | enum | バッジ種別。値の集合は §9 で確定（正本に具体値の列挙なし） |
  | `user_badges.granted_at` | timestamp | 付与日時（§5.1 user_badges 行） |
  | `user_badges`（複合 uniq） | — | `user_id × badge_key` を unique にして重複付与を防止（§9 で確定） |
  | 監査 / `created_at` 等 | — | §5 共通方針に準拠 |
- リレーション（§5.2）: `users 1─* user_badges`。集計入力として `users 1─* articles`（投稿数）、`articles 1─* reactions`（被リアクション数）を**読み取り参照**。
- enum: `Badge\Key`（仮称）をバック付き enum で新設（§10 の表に未掲載 → §9 で値とクラス名を確定）。`Notification\Type`（§10）にはバッジ用種別が無いため、通知連携時は追加要否を §9 で判断。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須（§9 認証境界 §6）。`ApiResponse` エンベロープ（`data/message/result`）準拠（§6 可観測性）。
> 注: §9 の代表エンドポイント表に gamification 専用 API は**未掲載**。露出範囲は §9（未確定）で確定する。以下は候補。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/me/contributions`（候補） | 自分の貢献度内訳・保有バッジ取得 | （body なし。認証セッション） | `{ contribution_points, breakdown?, badges: [{ badge_key, granted_at }] }` | 401 / 500 |
| GET | `/users/{user_id}/badges`（候補） | 他ユーザーの公開バッジ一覧 | path: `user_id` | `{ contribution_points, badges: [...] }` | 401 / 404 |

- 上記は**候補**。最小構成として「専用 API を持たず user-profile のレスポンスに `contribution_points` / `badges` を含めるだけ」も選択肢（§9）。
- 集計トリガはユーザー向け同期 API ではなく、**イベント駆動 + Queue Job / バッチ**（§6 非同期）。手動再集計用の内部コマンド（artisan）を持つかは §9。
- 失敗時は `ApiResponse` の `unauthenticate`/`notFound`/`serverError`（§6 / php.md 定型）。

## 6. UI / 画面設計
> プロフィール画面の描画は user-profile 側。本機能固有 UI は最小。
- 画面 / 状態:
    - プロフィール上の**貢献度ポイント表示**・**バッジ一覧（アイコン/ラベル）**（描画は user-profile、データは本機能）。
    - バッジ獲得の通知/トースト（notifications 連携が確定した場合のみ・§9）。
    - 状態: ローディング / 取得成功 / 401（ログイン導線）/ 404（他ユーザー不在）。
- 主要操作:
    - 貢献度・バッジの取得（`GamificationAPI`（仮）または user-profile API に内包・§9）。`client` 経由（typescript.md）。
    - 読み取り中心。ユーザーがポイント/バッジを直接操作する書き込み UI は持たない。
- 入力バリデーション・エラー表示:
    - ユーザー入力がほぼ無いため検証は最小。`ApiError` を捕捉して握り潰さない（typescript.md）。
- UI 規約: Chakra UI v3 ＋ `components/ui/` ラッパ、DTO 型は `api/types.ts` に集約（typescript.md）。バッジアイコンの表現は §9。

## 7. 技術選定と根拠
- 採用:
    - 既存 `Team` 縦切り（§6 アーキテクチャ）を踏襲したレイヤード構成（Input/Output/UseCase/Repository interface+impl/Controller/Request/Resource）。
    - **非同期集計**: 集計・バッジ付与は Laravel Queue（既存 `jobs`）で実行（§6 可用性/非同期「重い集計は Queue へ。失敗はリトライ」）。
    - 区分値は**バック付き enum**（`badge_key`・§5/§10 方針）。DTO は final + readonly、`#[Override]`（§6 保守性 / php.md）。
    - レスポンスは `ApiResponse`、文言は `lang/en/*.php` 翻訳キー（§6）。
- 理由:
    - 正本 §6 が非同期/Queue を非機能要件として明記。被リアクション集計は記事・反応の規模に比例して重くなり得るため、同期 API ではなくイベント駆動 + Job が整合的。
    - 集計値の保存先（`users.contribution_points`）と付与履歴（`user_badges`）が正本に定義済みで、表示は user-profile が担う分業が明確。
- 却下した代替案 / 却下理由:
    - 表示のたびに投稿数・被リアクションを**都度集計**してポイントを算出 → 一覧/プロフィールで N+1・重い集計を毎回実行（§6 性能違反）。集計済み値を `users.contribution_points` に保持する正本方針を採用。
    - 集計を Controller / UseCase の同期処理に埋める → リクエスト遅延・失敗時の整合崩れ。Queue Job 経由（§6）。
    - `App\Models` を UseCase から直接触って集計 → 依存方向違反（php.md）。Repository interface 経由（Infra のみ Eloquent）。
    - バッジ種別を生文字列で散らす → §5/§10 の enum 方針違反。`badge_key` enum 化。

## 8. エッジケース・非機能
- エッジケース:
    - リアクション解除・記事 archived/削除でポイントが過剰計上されたまま残る → 減算 or 再集計で整合（方式 §9）。下限 0 を割らない。
    - 同一バッジの**重複付与**（同条件が複数回成立）→ `user_badges` の `user_id × badge_key` unique で防止（§9）。
    - 自分の記事への**自己リアクション**を貢献度に含めるか（不正水増し防止）→ §9 で算定式と併せて確定。
    - 集計 Job の重複実行・順序逆転（公開→解除が前後）→ 冪等な再集計設計が安全（§9）。
    - 未集計（P3 リリース前 / 新規ユーザー）→ `contribution_points = 0`・バッジ無しで 200。
- 認可 / 権限（§8）:
    - ポイント/バッジの**自動付与はシステム責務**。ユーザー API からの改変は不可。
    - `GET /me/contributions` は本人のみ。他ユーザーのバッジは公開範囲のみ表示（§8 可視性思想、詳細 §9）。
    - `api/*` は Keycloak セッション必須（§6 認可）。
- 失敗時の挙動:
    - 同期 API は Controller で `try/catch` → `Log::error(__('messages.error', ...))` ＋ `report($e)` ＋ `ApiResponse::serverError()`（§6 / php.md 定型）。
    - 集計 Job 失敗は Queue のリトライに委ねる（§6）。リトライ上限超過時のログ/通知方針は §9。
- 性能・可用性で意識する点:
    - 集計は非同期（§6）。表示は保存済み `contribution_points` 読むだけで軽量。
    - 一覧でバッジを返す場合は N+1 回避（Infra で eager load・§6 性能）。
    - 大量リアクション時のバッチ集計の負荷分散（イベント単位 or 定期バッチ）は §9。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules（中身は転記しない・参照のみ）:
    - バックエンド: `.claude/rules/php.md`（PHPUnit 12 / Unit・Feature / 行カバレッジ 100% / Mockery / `#[Test]` / AAA）。
    - フロントエンド: `.claude/rules/typescript.md`（型エラーなし・ビルド可・`client` 経由・`ApiError` 捕捉）。
    - 全体: `.claude/rules/general.md`（Definition of Done）、§12 受入基準。
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - 集計 UseCase: ユーザー / リアクション / 記事の各 `RepositoryInterface` を Mockery でモック。投稿数・被リアクション数を与えてポイント計算式を検証（式は §9）。
        - Queue Job: 同期実行（`Queue::fake()` / Bus）でディスパッチと冪等性を検証。実 Queue に依存しない。
        - Controller catch: `$this->app->instance(Interface::class, $throwingMock)`、本人特定は `Auth::shouldReceive(...)`。
        - バッジ付与の境界（しきい値ちょうど / 直前 / 直後）と重複付与防止（unique 制約 → 例外 or no-op の分岐到達）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1/US2: 公開・被リアクションで加算、解除/アーカイブで整合（下限 0、過剰計上なし）。
        - US3: しきい値到達でバッジ付与、同条件再成立で重複付与しない。
        - US4/US5: 自分/他者の貢献度・バッジ取得のレスポンス形と公開範囲。
        - 自己リアクション除外（採用時）の算定。
    - DB / 外部サービス依存で注意する点:
        - Feature テストは MySQL テスト DB（`learning_portal_testing` / `.env.testing`）前提。sqlite 不可（生 SQL マイグレーション）。
        - 集計入力に `articles`（status/visibility）・`reactions`（kind）のシードが必要（article-core / article-reactions 連携）。
        - `user_badges` の複合 unique 制約をマイグレーションとテストで担保。

## 9. 未確定（brainstorming で詰める）
- [ ] **ポイント算定式**: 投稿（published）1 件 = 何点、被リアクション 1 件 = 何点、`kind`（like/helpful/insightful）で重み付けするか。自己リアクション・自己投稿の扱い、上限の有無。正本に式は無い。
- [ ] **バッジ種別と付与条件**: `Badge\Key` enum の値集合（例: 初投稿 / 投稿数しきい値 / 被リアクション数 / 連続投稿 等）と各付与条件。enum クラス名・名前空間（§10 の表へ追記要否）。
- [ ] **集計タイミング（同期/バッチ/Queue）**: イベント駆動（記事公開・リアクション付与/解除ごとに Job ディスパッチ）か、定期バッチ（cron/scheduler で再集計）か、両者併用か。冪等な全再集計コマンド（artisan）を持つか。リトライ上限超過時の扱い。
- [ ] **整合方式（加算/減算 vs 再集計）**: 解除・アーカイブ・削除時に減算するか、対象ユーザーを毎回フル再集計するか。下限 0 保証の実装方法。
- [ ] **API 露出範囲**: gamification 専用 API（`/me/contributions`・`/users/{id}/badges` 等）を新設するか、user-profile のレスポンスに `contribution_points` / `badges` を内包するだけにするか（§9 代表表に専用 API 無し）。内訳（breakdown）を返すか。
- [ ] **バッジの公開範囲**: 他ユーザーへ全バッジ公開か、公開/非公開フラグを持つか（`user_badges` に可視性列は無い）。
- [ ] **通知連携**: バッジ付与時に通知を出すか。出すなら `Notification\Type`（§10）に専用種別（例 `badge_granted`）を追加するか、notifications 機能側で扱うか。
- [ ] **バッジ表示メタ**: アイコン/ラベル/説明をどこに持つか（フロント定数 / DB / 翻訳キー `lang/en/*.php`）。多言語化方針。
- [ ] **既存 P2/P3 前提**: `article-reactions`（P2）実装完了が前提。MVP 段階での `contribution_points` の扱い（常に 0 表示）との接続。
