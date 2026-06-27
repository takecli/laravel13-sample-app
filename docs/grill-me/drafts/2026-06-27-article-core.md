# ドラフト: 記事（ナレッジ）コア 〔article-core〕

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 記事（ナレッジ）コア 〔`article-core`〕
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - **依存（前提）**: `team-membership`（§4.4）— 認可（ロール×権限）の判定元。`team-core`（§4.3）— `team_id`・チーム `visibility`。`auth`（§4.1）— 認証ユーザー（著者/操作者）。
    - **本機能に依存する後続**: `article-tags`（§4.8）/ `article-attachments`（§4.9）/ `article-comments`（§4.10）/ `article-revisions`（§4.7）/ `article-reactions`（§4.11）/ `bookmarks`（§4.12）/ `collections`（§4.13）/ `search`（§4.14）。
    - **移行メモ**: 旧 `notes` テーブルを正本 §5 `articles` に作り直す前提。既知バグ（旧 `app/Domains/Enums/Notes/Status.php` の PSR-4 名前空間不一致＝namespace が `App\Domain\Enum\Notes` でパス不一致）は、新 `App\Domains\Enums\Article\Status` を**正しい PSR-4 名前空間で**作成して解消する。

## 1. 目的（Why）
チームの暗黙知を「書く・読む・育てる」サイクルで形式知化する本プロダクトの**中核**（§1.1）。記事（ナレッジ）を Markdown で作成・取得・一覧・更新・論理削除でき、`draft → in_review → published → archived` のワークフロー（§7.1）で品質と鮮度を担保し、`team`/`public` の公開範囲（§10 `Article\Visibility`）で社外秘と全社公開を出し分ける。一覧はチーム・状態・タグ・著者・キーワードで絞り込め、再利用・検索の起点となる（§4.6, §1.3）。

## 2. スコープ
- やること:
    - 記事 CRUD: 作成・取得（単体）・一覧・更新・削除（**論理削除** `deleted_at`、§5.1 監査列方針）。
    - 本文は **Markdown**（プレーンテキスト保存。XSS は表示時にサニタイズ／§6 セキュリティ）。
    - ワークフロー状態遷移 API（`PUT /articles/{id}/status`）: `submit / approve / reject / archive / unpublish / restore`（§7.1）。
    - `visibility`（`team` / `public`）と `status` を組み合わせた**可視性制御**（§6 認可・§7.1・§8）。
    - 一覧の絞り込み（**チーム・状態・タグ・著者・キーワード**）＋ページング（既定 `Pagination`、§6 性能）。
- やらないこと（今回対象外）:
    - 改訂履歴 `article_revisions` の記録・参照（→ `article-revisions` §4.7／P2）。**ただし §9 で「更新時に版を残すフックを今入れるか」を要確認**。
    - タグの作成・付け替え本体（→ `article-tags` §4.8）。一覧の**タグ絞り込み条件**は本機能が受け取るが、タグ CRUD は別機能。
    - 添付（→ `article-attachments` §4.9）、コメント（§4.10）、リアクション（§4.11）、ブックマーク（§4.12）。
    - 全文検索の高度化／Meilisearch 等への差し替え（→ `search` §4.14）。MVP の一覧キーワード絞り込みは MySQL `LIKE`/全文索引で足りる範囲のみ。
- 非ゴール（誤解されやすいので明示）:
    - WYSIWYG リッチエディタ・リアルタイム共同編集（§13）。MVP は Markdown テキストのみ。
    - AI 自動要約・自動タグ付け（§13）。
    - 記事の物理削除（論理削除のみ）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1（US-A1）: Contributor は学んだ手順を **下書き（draft）** として作成し、本文 Markdown・タイトルを保存できる。
- [ ] US2（US-A1）: Contributor は自分の draft を **submit** して `in_review` に進められる。
- [ ] US3（US-A2）: Team Admin/Owner は `in_review` の記事を **approve→published** または **reject→draft** にできる。
- [ ] US4: Team Admin/Owner は `published` 記事を **archive→archived**、また **unpublish→draft**、**restore→draft** できる（§7.1 の遷移）。
- [ ] US5: Contributor/Admin は自分（または権限保持者は他者）の記事を更新できる（タイトル・本文・visibility）。
- [ ] US6: 操作者は記事を**論理削除**でき、削除済みは一覧・取得から除外される。
- [ ] US7（US-R1 の一部）: Learner は記事一覧を **チーム・状態・タグ・著者・キーワード** で絞り込み、ページングで閲覧できる。
- [ ] US8: 閲覧時、`published`＋`team` はメンバーのみ、`published`＋`public` は認証ユーザー全員（Guest 含む）、`draft/in_review/archived` は著者と Admin/Owner のみ可視（§7.1, §8）。

## 4. データモデル設計
- エンティティ: `articles`（§5.1）。新規 enum `App\Domains\Enums\Article\Status` / `App\Domains\Enums\Article\Visibility`（§10、PSR-4 名前空間を正しく付与）。
- 属性 / 型 / 制約（§5 方針 + §5.1 articles 行）:
    - `id` `CHAR(36)` UUID（PK）。
    - `team_id` `CHAR(36)`（FK→`teams`、必須）。
    - `author_user_id` `CHAR(36)`（FK→`users`、必須＝著者）。
    - `title` `String`（必須）。
    - `slug` `String`（記事の slug。**生成規則・ユニーク範囲は §9 未確定**）。
    - `body` `Text`（Markdown、必須）。
    - `status` enum `Article\Status`（`draft`/`in_review`/`published`/`archived`、既定 `draft`）。
    - `visibility` enum `Article\Visibility`（`team`/`public`、既定は §9 未確定）。
    - `published_at` `DateTime`（null 可。公開時刻。**セット/クリアの扱いは §9 未確定**）。
    - 監査列: `created_at` / `updated_at`、作成/更新/削除ユーザーID、論理削除 `deleted_at`（§5 既存方針踏襲）。
- リレーション（§5.2）:
    - `articles *─1 teams`（所属チーム）。
    - `articles *─1 users(author)`（著者）。
    - `articles 1─* article_revisions / article_attachments / comments / reactions / bookmarks`（後続機能側で参照）。
    - `articles *─* tags`（`article_tags` 中間、`article-tags` 側）。
- インデックス方針（§6 性能・N+1 回避）: 一覧フィルタ列（`team_id`, `status`, `author_user_id`, `deleted_at`）に索引。キーワード絞り込みは `title`/`body` の全文索引または `LIKE`（MVP は MySQL）。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須。`ApiResponse` エンベロープ（`data/message/result`）準拠（§9, §6）。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/articles` | 記事一覧（絞り込み＋ページング） | クエリ: `team`, `status`, `tag`, `author`, `q`(keyword), `page`, `limit`, `sort` | `data`: 記事配列＋ページング情報 | 401（未認証）/ 422（不正なフィルタ値）/ 500 |
| POST | `/articles` | 記事作成（既定 `draft`） | body: `team_id`, `title`, `body`, `visibility`（任意で `tag` 等） | 作成された記事（201 相当） | 401 / 403（チーム非メンバー）/ 422 / 500 |
| GET | `/articles/{id}` | 記事単体取得 | path: `id` | 記事 1 件 | 401 / 403（可視性違反）/ 404（不存在/論理削除済）/ 500 |
| PUT | `/articles/{id}` | 記事更新（title/body/visibility） | path: `id`、body: `title`, `body`, `visibility` | 更新後の記事 | 401 / 403（編集権限なし）/ 404 / 422 / 500 |
| DELETE | `/articles/{id}` | 記事削除（論理削除） | path: `id` | 204/成功メッセージ | 401 / 403 / 404 / 500 |
| PUT | `/articles/{id}/status` | 状態遷移（submit/approve/reject/archive/unpublish/restore） | path: `id`、body: `action`（または `status`）。**入力形式は §9 未確定** | 遷移後の記事（status 反映） | 401 / 403（権限/ロール不足）/ 404 / 409 or 422（不正な遷移）/ 500 |

- バリデーションは FormRequest（§6 セキュリティ）。検証済み入力は `toInput()` で Application 層 Input DTO に詰め替え（`php.md` 規約参照）。
- enum 変換（`status`/`visibility` 文字列→`Article\Status`/`Article\Visibility`）は FormRequest 側で実施。

## 6. UI / 画面設計
> MVP は Markdown テキスト編集（WYSIWYG なし、§13）。フロントは `resources/js`（`typescript.md` 準拠）。

- 画面 / 状態:
    - 記事一覧画面: フィルタ（チーム/状態/タグ/著者/キーワード）＋ページネーション、ローディング/空/エラー状態。
    - 記事詳細画面: Markdown レンダリング表示、状態バッジ（draft/in_review/published/archived）、visibility 表示、状態遷移操作（権限に応じて表示）。
    - 記事作成/編集画面: タイトル・本文（Markdown テキストエリア）・visibility 選択・保存（下書き保存）。
- 主要操作:
    - 作成（draft 保存）/ 編集 / 削除（論理削除の確認）。
    - 状態遷移ボタン（submit / approve / reject / archive / unpublish / restore）— ロール・現在状態で活性制御。
- 入力バリデーション・エラー表示:
    - タイトル必須・本文必須（クライアント側）。サーバ 422 を `ApiError` で受け、フィールド単位に表示。
    - 403（権限/可視性）・404 はユーザー向けメッセージへ。`client`（`api/client.ts`）経由・`api/article.ts` に集約（`typescript.md`）。

## 7. 技術選定と根拠
- 採用:
    - レイヤード/クリーンアーキテクチャの縦切り（`Team` 参照実装を踏襲）: Input/Output/UseCase/Search(Filter)/Result/Repository interface(`Domains/Repositories`)＋impl(`Infra/Persistence`)/Controller/Request/Resource（§6, `CLAUDE.md`/`php.md`）。
    - 区分値はバック付き enum（`Article\Status` / `Article\Visibility`、§10）。状態遷移ロジックは Domain（enum もしくは記事エンティティ）に閉じる。
    - 本文は Markdown を**プレーンテキストで保存**し、レンダリング/サニタイズは表示層（XSS 対策、§6）。
    - 一覧の絞り込みは Domain の `〜Search`（Filter）で表現し、Infra で Eloquent クエリへ変換（N+1 回避の eager load、§6 性能）。
- 理由:
    - 後続の `article-*` 群が本機能に依存するため、CRUD＋状態遷移＋可視性の境界を Domain で明確に切ることが拡張性に効く。
    - enum 化で状態遷移の不正分岐をコンパイル/型レベルで縛り、テストで全分岐を網羅しやすい。
- 却下した代替案 / 却下理由:
    - 状態遷移を文字列フィールド直書きで管理 → 不正遷移の検知漏れ・テスト網羅困難。enum＋遷移ガードに統一。
    - 物理削除 → 監査・復元要件（§5 監査列・論理削除方針）に反する。論理削除のみ。
    - MVP からの検索エンジン導入（Meilisearch 等）→ YAGNI（§14 は `search`/P2）。MVP は MySQL `LIKE`/全文索引で抽象化のみ意識。

## 8. エッジケース・非機能
- エッジケース:
    - 不正な状態遷移（例: `draft` から直接 `archive`、`archived` から `submit`）→ §7.1 の許可遷移以外は拒否（409/422）。
    - 論理削除済み記事への取得/更新/遷移 → 404 扱い。
    - `published`→`unpublish`→`draft` 後の `published_at` の扱い（§9 未確定）。
    - 非メンバーによる作成・他者記事の編集/削除 → 403。
    - キーワード/タグ/著者フィルタが 0 件 → 空配列＋ページング情報。
- 認可 / 権限（§8 マトリクス・§7.1）:
    - 作成・自分の記事編集: Owner/Admin/Member（Guest 不可）。
    - レビュー承認（公開へ）・他者記事の編集/削除: Owner/Admin のみ。
    - `published`＋`team` 閲覧: チームの Owner/Admin/Member。`published`＋`public` 閲覧: Guest 含む全認証ユーザー。
    - `draft`/`in_review`/`archived` 閲覧: 著者と Admin/Owner のみ。
    - 認可判定は `team-membership` のロール解決に依存。
- 失敗時の挙動:
    - 例外はコントローラで `Log::error`＋`report()`＋`ApiResponse::serverError()`（§6 可観測性, `php.md`）。
    - レスポンスは `ApiResponse` エンベロープ統一。ユーザー文言は `lang/en/*.php` 翻訳キー。
- 性能・可用性で意識する点:
    - 一覧は必ずページング（既定 `Pagination`）。フィルタ列に索引、Infra で必要列 eager load（N+1 回避）。
    - キーワード検索はインデックス/全文索引前提（§6）。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`（PHPUnit 12 / Unit・Feature / 行カバレッジ 100% / `#[Test]` 属性 / Mockery / Feature は MySQL テスト DB）。
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`（`tsc --noEmit` 型エラーなし・ビルド可、`client` 経由）。
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - 状態遷移の**全分岐網羅**: `submit/approve/reject/archive/unpublish/restore` の正常遷移、および**不正遷移（許可外）を全て拒否**することを検証（§7.1）。
        - 認可 × visibility の**網羅**: ロール（Owner/Admin/Member/Guest）×（`status`×`visibility`）の可視性マトリクス（§8）を Feature で総当たり。
        - 例外分岐到達: Repository interface を Mockery で `andThrow` させ UseCase の失敗系、Controller catch は `$this->app->instance(...)` で `serverError` 到達を検証。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US2/US3/US4（状態遷移）、US8（可視性制御）、US6（論理削除後の 404）、US7（フィルタ＋ページング各条件）。
    - DB / 外部サービス依存で注意する点:
        - マイグレーションは MySQL 専用生 SQL（`UUID_TO_BIN`/`ENUM`/`COMMENT`）。sqlite では動かないため Feature は `learning_portal_testing`/`.env.testing` 前提。
        - 旧 `notes` からの作り直しに伴い、新 `articles` マイグレーションとテストデータ（factory/seeder）を整備。

## 9. 未確定（brainstorming で詰める）
- [ ] **slug 生成規則**: タイトルから自動生成か手入力か。ユニーク範囲（全体一意 / チーム内一意）。重複時のサフィックス付与規則。日本語タイトルの slug 化方針。
- [ ] **`published_at` の扱い**: `approve→published` 時に自動セットか。`unpublish`/`archive` 時にクリア/保持か。再公開時は更新するか初回を保つか。
- [ ] **`visibility` の既定値**: 作成時の既定を `team` とするか明示必須とするか。`status` 変更時に visibility 変更を許すか。
- [ ] **状態遷移 API の入力形式**: `action`（submit/approve/…）を受けるか、目標 `status` を受けて遷移ガードするか。エンドポイント設計（単一 `PUT /status` に集約で確定だが body スキーマ未確定）。
- [ ] **unpublish / restore / archive の権限**: §8 マトリクスに明示がない（approve は Owner/Admin）。unpublish・restore・archive を誰が実行可能か（著者本人を含むか）。
- [ ] **「権限保持者」の定義**（§7.1 注記の「または権限保持者」）: Owner/Admin 以外に承認権限を持つロール拡張の余地があるか。
- [ ] **改訂履歴フック**: `article-revisions`（P2）導入前提として、`PUT /articles/{id}` 更新時に版を残すための拡張ポイント（イベント/フック）を本機能で用意しておくか、完全に後送りか。
- [ ] **一覧フィルタの結合条件**: タグ複数指定時の AND/OR、キーワードの対象（title のみ / title+body / +tag）、ソートの既定（更新日時 / published_at）。
- [ ] **論理削除済み記事の状態遷移可否**: restore 系で deleted の復元を扱うか（削除と archive/restore の責務分離）。
