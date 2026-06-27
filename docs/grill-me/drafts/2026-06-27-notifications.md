# ドラフト: 通知（notifications）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 通知（notifications）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存（イベント発生源）: `article-comments`（`comment_added`）、`article-reactions`（`reaction_added`）、`team-access`（`join_request_resolved` / `invitation_received`）、`article-core`（`article_published`）
    - 関連: `auth`（通知の受信者＝認証ユーザー `users`）、`user-profile`（通知 payload で著者/操作者の表示名等を参照する場合）
    - 基盤: Laravel Queue（既存 `jobs`。§6 可用性/非同期）
    - リリース段階: P3（Phase 3。各イベント機能の実装後に横断で乗せる。§4.15 / §11）

## 1. 目的（Why）
ユーザーが自分に関係するイベント（自分の記事へのコメント/リアクション、参加申請の結果、招待の受領、記事の公開）を **アプリ内通知** として受け取り、未読/既読を管理して見逃しを防ぐ。投稿・参加文化の醸成（§1.3）と、US-N1（「自分の記事へのコメント/リアクション」「参加申請の結果」を通知で受け取る）を満たす（要件 §4.15 / US-N1）。

## 2. スコープ
- やること:
    - アプリ内通知の **永続化**（`notifications` テーブル: `user_id` / `type` / `payload`(JSON) / `read_at`）。
    - 受信者向け API: 通知一覧取得（`GET /me/notifications`）、個別既読（`PUT /me/notifications/{id}/read`）、一括既読（`PUT /me/notifications/read-all`）。
    - 既読管理（`read_at` の null=未読 / 非null=既読への遷移）。未読件数の提供（要否は §9）。
    - イベント発生時の通知生成（コメント/リアクション/申請結果/招待/記事公開の各イベント → `Notification\Type` ごとに 1 レコード）。
    - 通知生成・配信は **Laravel Queue** に載せる（同期処理をブロックしない。§6）。
- やらないこと（今回対象外）:
    - 各イベント自体の業務処理（コメント投稿・リアクション・承認/招待・記事公開のロジックは各機能の責務。本機能は「発火を受けて通知を作る」結線のみ）。
    - メール/プッシュ/Slack 等の **外部チャネル配信**（本機能はアプリ内通知。外部連携の有無は §9）。
    - 通知設定（ユーザーごとの ON/OFF・ミュート・ダイジェスト）。本書に記載なし＝対象外（必要なら §9 で提起）。
- 非ゴール（誤解されやすいので明示）:
    - 通知をユーザーが API から**作成/送信**すること。通知は **イベント駆動で内部生成**のみ（公開 API は読み取り＋既読更新だけ）。
    - リアルタイム push（WebSocket/SSE）配信。MVP/本書範囲ではポーリングによる取得を前提（リアルタイム化は §9）。
    - 既読以外の状態（アーカイブ・削除・スヌーズ）の管理。本書では `read_at` の二値のみ。

## 3. ユースケース / ユーザーストーリー
- [ ] US1（US-N1）: 自分の記事に他者が **コメント**すると `comment_added` 通知が届き、一覧で確認できる。
- [ ] US2（US-N1）: 自分の記事に **リアクション**が付くと `reaction_added` 通知が届く。
- [ ] US3（US-N1）: 自分が出した **参加申請が承認/却下**されると `join_request_resolved` 通知が届く。
- [ ] US4: private チームへの **招待を受領**すると `invitation_received` 通知が届く。
- [ ] US5: 関心のある（著者/チームメンバー等の対象）記事が **公開**されると `article_published` 通知が届く（配信対象範囲は §9）。
- [ ] US6: ユーザーは自分の通知一覧を取得し、未読/既読を区別して閲覧できる。
- [ ] US7: ユーザーは通知を **個別に既読**化、または **一括既読**化できる。

## 4. データモデル設計
（正本 §5.1 の `notifications` 行、§5.2 リレーション、§10 enum を転記）

- エンティティ:
    - `notifications`（アプリ内通知。受信者 1 ユーザーにつき複数）
- 属性 / 型 / 制約:
    - 共通: `id` `CHAR(36)` UUID、`created_at/updated_at` ＋ 作成/更新/削除ユーザーID の監査列、論理削除対象は `deleted_at`（正本 §5 冒頭の全テーブル共通方針。通知に論理削除を持たせるかは §9）。
    - `notifications`: `user_id`（受信者）, `type`（`Notification\Type`）, `payload`(JSON), `read_at`（nullable。null=未読 / timestamp=既読）。
    - enum（§10 バック付き）:
        - `Notification\Type`: `comment_added` / `reaction_added` / `join_request_resolved` / `invitation_received` / `article_published`
    - `payload`(JSON): 通知種別ごとにフロントが表示・遷移先を組み立てるための文脈データ（記事ID/タイトル、操作者ID/表示名、チームID、コメントID 等）。**具体スキーマは type ごとに §9 で確定**。
- リレーション（§5.2）:
    - `users 1─* notifications`（受信者ごとに紐づく）。
    - payload 内の参照（article_id / team_id / actor_user_id 等）は **JSON 内の論理参照**（FK は張らない想定。要否は §9）。
    - インデックス: `user_id` ＋ `read_at`（未読絞り込み・件数集計用）、`created_at`（新着順）を想定（具体は §9）。

## 5. API 設計
（正本 §9 の通知行。エンベロープは `ApiResponse`、prefix `/api/v1`、Keycloak セッション必須。すべて「自分(`/me`)」スコープ）

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/me/notifications` | 自分の通知一覧（新着順・ページング） | `unread?`(未読のみ)・`type?`・ページング（既定 `Pagination`） | 通知一覧（id/type/payload/read_at/created_at）＋ページング（未読件数の同梱は §9） | 401 未認証 |
| PUT | `/me/notifications/{id}/read` | 個別既読化（`read_at` をセット） | （id はパス） | 更新後の通知（read_at 設定済） | 401 / 403 他人の通知 / 404 不在 / 409 既読済（冪等とするかは §9） |
| PUT | `/me/notifications/read-all` | 全未読を一括既読化 | — | 既読化件数 等（レスポンス形は §9） | 401 |

- 認可: いずれも **認証ユーザー自身の通知のみ**操作可（`user_id = 認証ユーザー`）。他人の通知ID指定は 403/404。
- エラーは `ApiResponse`（`unauthenticate`/`forbidden`/`notFound`/`badRequest`/`serverError`）に対応付け。HTTP コードと `ApiResponse` メソッドの正確な対応は §9 で詰める。

## 6. UI / 画面設計
（本機能はバックエンド API 中心。フロント実装の有無/範囲は brainstorming で確定。下記は想定レベル）
- 画面 / 状態:
    - ヘッダーの通知ベル: 未読バッジ（件数）。クリックでドロップダウン/通知一覧へ。
    - 通知一覧（ページ or ドロップダウン）: 種別アイコン＋本文、未読は強調、既読は淡色。新着順・ページング。
    - 各通知をクリックで該当リソース（記事/コメント/招待ページ等）へ遷移＋既読化。
    - 「すべて既読にする」操作。
- 主要操作: 一覧取得（必要に応じポーリング更新）、個別既読、一括既読、通知から該当ページへ遷移。
- 入力バリデーション・エラー表示:
    - 一覧クエリ（`unread`/`type`/ページング）の型検証。
    - 既読 API の 403/404 は静かに無視 or トースト（方針は §9）。

## 7. 技術選定と根拠
- 採用:
    - 通知は **専用 `notifications` テーブルに永続化**（`type` enum ＋ `payload` JSON ＋ `read_at`）。Laravel 標準 Notifications の DB チャネルではなく、本リポのレイヤード構成（Domain エンティティ / Repository ポート / Infra 実装）に載せる。
    - 既読は `read_at`(nullable timestamp) の二値で表現（別 status 列を持たない）。
    - 通知生成・配信は **Laravel Queue**（既存 `jobs`）へオフロード。失敗はリトライ（§6 可用性/非同期）。
    - 公開 API は **読み取り＋既読更新のみ**。生成はイベント駆動の内部処理。
- 理由:
    - 正本（§4.15 / §5 `notifications` 行 / §6 非同期 / §10 enum）の確定設計に一致。`type`/`payload`/`read_at` の 3 点が指定済み。
    - JSON payload にすることで type ごとに異なる文脈を 1 テーブルで保持でき、新 type 追加時のスキーマ変更を避けられる（enum 追加で拡張）。
    - 非同期化でコメント/リアクション等の主処理が通知生成でブロックされない。
- 却下した代替案 / 却下理由:
    - type ごとにテーブル分割 → 一覧の横断取得・件数集計が複雑化。1 テーブル＋JSON を採用（正本指定どおり）。
    - `read`(boolean) 列 → 既読日時が残らず、将来「いつ読んだか」を失う。`read_at`(timestamp) を採用（正本指定どおり）。
    - 同期生成（リクエスト内で通知INSERT） → 受信者が多い場合（例: チーム全員への article_published）に主処理が遅延。Queue を採用（§6）。
    - Laravel 標準 `Notification`/`DatabaseChannel` 全面採用 → Eloquent/フレームワーク前提が Domain に漏れる。レイヤード規約（Domain は Laravel 非依存）と整合させ、Infra に閉じる。

## 8. エッジケース・非機能
- エッジケース:
    - 自分のイベントで自分に通知（自分の記事に自分でコメント/リアクション）→ 自己通知は抑止する想定（要否・条件は §9）。
    - 同一イベントの重複通知（再試行・多重発火）→ 冪等化/重複抑止の方針は §9。
    - 既読済み通知への再既読 → 冪等に成功扱いとするか 409 とするか（§9）。
    - 大量受信者への article_published → Queue でファンアウト。配信対象の母集団定義が必要（§9）。
    - 参照先リソースが後から削除（記事/コメント削除）された通知 → payload は残るがリンク先が 404。表示時のフォールバック（§9）。
    - 受信者ユーザーが退会/無効 → 通知生成スキップ or 残置（§9）。
- 認可 / 権限（§8 マトリクス補足。通知は §8 の表に明記なし＝本機能固有ルール）:
    - 通知の閲覧・既読操作は **受信者本人のみ**（`user_id = 認証ユーザー`）。
    - `api/*` は Keycloak セッション必須。
- 失敗時の挙動:
    - コントローラ例外は `Log::error` ＋ `report()` の後 `ApiResponse::serverError()`（§6 可観測性 / コントローラ定型）。
    - 通知生成 Job の失敗は Queue のリトライに委ねる。主処理（コメント等）は通知生成失敗でロールバックしない（通知は付随処理）。
- 性能・可用性で意識する点:
    - 一覧 API はページング必須・N+1 回避（payload は JSON 自己完結なので join は最小）。`user_id`＋`read_at` 索引で未読絞り込み/件数を高速化。
    - 通知生成は非同期（Queue）。ファンアウト時のジョブ分割を意識。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`（フロント実装を行う場合）
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - 通知生成の **Queue/Job** はフェイク（`Queue::fake()` 等）で「enqueue されたこと」を検証し、Job 本体は単体で別途検証（実ジョブを同期実行しない）。
        - 各イベント機能との結線（Observer/Event/Listener 等、方式は §9）は **イベント発火のモック/フェイク**で「通知が生成される」ことを検証。逆に、通知側は「イベントを受けたら正しい type/payload を作る」単位で検証。
        - UseCase は Repository interface（`NotificationRepositoryInterface` 想定）を Mockery で差し替え、例外分岐（`andThrow`）を到達させる。Controller catch は `$this->app->instance(...)` ＋ throwing mock。
        - 既読化の時刻（`read_at`）は現在時刻依存 → 時刻を固定して検証。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1〜US5: 各 `Notification\Type` ごとに、対応イベントから正しい type と payload で 1 レコード生成される（type 網羅）。
        - US6: 一覧が新着順・受信者スコープ・未読フィルタで返る。他人の通知が混ざらない（認可）。
        - US7: 個別既読で `read_at` がセットされ未読→既読へ遷移／一括既読で全未読が既読化（既読遷移を重点検証）。
        - 自己通知抑止・重複抑止（方針確定後にテストで担保）。
    - DB / 外部サービス依存で注意する点:
        - Feature テストは MySQL テスト DB 前提（マイグレーションは MySQL 専用の生 SQL（`UUID_TO_BIN`/`ENUM`/`COMMENT`）。sqlite 不可）。`payload` の JSON 列の読み書きを検証。
        - `user_id`＋`read_at` の索引・未読件数集計の挙動を確認。
        - 外部チャネル（メール/プッシュ）を採用する場合（§9）は Mail/通知ファサードをフェイクし実送信しない。

## 9. 未確定（brainstorming で詰める）
- [ ] **payload の JSON スキーマ**を `Notification\Type` ごとに確定（必須キー: 例 `comment_added` = article_id/title/comment_id/actor_user_id/actor_name 等、`article_published` = article_id/title/team_id 等）。バージョニング方針も含む。
- [ ] **通知生成のイベント結線方式**: (a) Eloquent Observer、(b) ドメインイベント＋Listener（`Event`/`Listener`）、(c) UseCase 内で明示的に通知 UseCase を呼ぶ。レイヤード規約（Domain は Laravel 非依存）との整合をどう取るか。
- [ ] **配信の非同期方式の具体**: 通知生成自体を Job 化するか（イベント→Job→INSERT）、INSERT は同期で「外部チャネル送信のみ」Job 化するか。リトライ回数・失敗時の扱い。
- [ ] **メール/プッシュ等の外部チャネル連携の有無**（本書はアプリ内通知が主。§4.15 は「アプリ内通知へ」。外部連携は対象外と確定してよいか）。
- [ ] `article_published` の **配信対象母集団**の定義（チームメンバー全員 / その記事をブックマーク or フォローした人 / 著者をフォローした人など。フォロー機能は本書になし）。
- [ ] **自己通知の抑止**ルール（自分の操作で自分に通知しない）の対象 type と条件。
- [ ] **重複通知の抑止/冪等化**（同一イベントの多重発火、再リアクションのトグル時など）。
- [ ] **未読件数**の提供方法（一覧レスポンスに同梱 / 専用 `GET /me/notifications/unread-count` を足すか）。本書 API には件数専用エンドポイントは無い。
- [ ] 一覧の **デフォルトソート/フィルタ仕様**（新着順固定か、`unread`/`type` クエリの正式な契約、ページング既定値）。
- [ ] 個別既読の **冪等性**（既読済みに対して 200 冪等成功か 409 か）と、`read-all` のレスポンス形（更新件数を返すか）。
- [ ] `notifications` に **論理削除（`deleted_at`）・監査列**を持たせるか（通知は使い捨て寄り。正本 §5 共通方針との折り合い）。古い通知の保持期間/パージ方針。
- [ ] payload 内参照（article_id/team_id/actor_user_id）に **FK を張るか / JSON 論理参照のみ**にするか。参照先削除時の表示フォールバック。
- [ ] 上表の HTTP ステータスと `ApiResponse` メソッド（forbidden/notFound 等）の正確な対応付け（他人の通知ID指定を 403 と 404 のどちらに寄せるか）。
- [ ] リアルタイム配信（WebSocket/SSE）やフロントのポーリング間隔（本書範囲はポーリング前提か）。
