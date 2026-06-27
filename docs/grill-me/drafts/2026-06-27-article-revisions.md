# ドラフト: 改訂履歴（article-revisions）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 改訂履歴（article-revisions）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - **依存**: `article-core`（§11「`revisions` は `article-core` に依存」）。記事本体の更新フローに乗る。
    - **関連**: `user-profile`（`editor_user_id` の表示名解決）、`team-membership`（閲覧/編集の認可ベース）。
    - リリース段階: **P2 / Phase 2**（§11「探す・磨く・参加導線」12 番目）。

## 1. 目的（Why）
- 記事は `draft → in_review → published → archived` の編集ワークフローで継続更新される（§4.6, §7.1）。**更新のたびに過去の内容が失われると、「いつ・誰が・何を変えたか」が追えず、信頼できる版の特定や巻き戻し判断ができない**（§1.2「信頼できる最新版がどれか分からない」課題）。
- US-A3（§3）: 「Contributor は記事を編集すると **改訂履歴（revision）** が残り、過去版を参照できる」を満たす。
- 本機能は**記事更新ごとに不変の版（snapshot）を `article_revisions` に追記し、版の一覧と単一版の参照を提供**する。差分（diff）の可視化は **UI 側の責務**（§4.7「差分は UI 側」）。

## 2. スコープ
- やること:
    - 記事更新時に `article_revisions` へ版を**追記（append-only）**。
    - 版の**一覧取得**: `GET /articles/{id}/revisions`（§9 改訂行）。
    - **単一版の参照**: `GET /articles/{id}/revisions/{version}`（§9 改訂行）。
    - 各版は `version`・`title`・`body`・`editor_user_id`・作成時刻を保持（§5 article_revisions 行）。
- やらないこと（今回対象外）:
    - **差分（diff）の算出・ハイライト表示**は UI 側（§4.7）。サーバは版の生データを返すのみ。
    - 過去版への**ロールバック / 復元**（正本に記載なし → §9 未確定として保留、今回実装に含めない）。
    - 版の**削除・編集**（履歴は不変。改竄しない）。
- 非ゴール（誤解されやすいので明示）:
    - リアルタイム共同編集・WYSIWYG（§13 非ゴール）。本機能は「更新後の確定スナップショット履歴」であり、編集中の自動保存やオペレーション単位の履歴ではない。
    - AI による要約・変更要約の自動生成（§13 非ゴール）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1（US-A3）: Contributor が記事を更新すると、その内容が新しい版として `article_revisions` に追記される。
- [ ] US2: 閲覧権限を持つユーザーが、記事の改訂履歴（版の一覧、新しい順）を取得し「いつ・誰が」を確認できる。
- [ ] US3: 閲覧権限を持つユーザーが、特定の `version` を指定して過去版の `title`/`body` を取得し、（UI 側で）現行版との差分を確認できる。
- [ ] US4: 存在しない記事 ID / 版番号を指定したとき、適切なエラー（404）が返る。

## 4. データモデル設計
- エンティティ: `article_revisions`（§5.1）。記事 1 件に対する不変の改訂スナップショット。
- 属性 / 型 / 制約（§5 共通方針 + article_revisions 行）:
    - `id`: `CHAR(36)` UUID（§5 全テーブル共通）。
    - `article_id`: `CHAR(36)`、`articles.id` への FK。
    - `version`: 整数。記事内で単調増加する版番号（採番方式は §9 未確定）。`(article_id, version)` で複合ユニークが妥当。
    - `title`: その版時点のタイトル（snapshot）。
    - `body`: その版時点の本文 Markdown（snapshot）。
    - `editor_user_id`: その版を作成（=記事を更新）したユーザー ID。`users.id` への FK。
    - `created_at` / `updated_at`: §5 共通監査列。履歴は不変のため実質 `created_at` が版の確定時刻。
    - **論理削除（`deleted_at`）の要否は §9 未確定**（履歴は不変＝物理的に消さない方針が自然だが、正本は明記なし）。
- リレーション（§5.2）:
    - `articles 1─* article_revisions`（記事 1 件に複数版）。
    - `article_revisions *─1 users(editor)`。

## 5. API 設計
> `prefix: /api/v1`、Keycloak セッション必須、`ApiResponse` エンベロープ準拠（§9 冒頭）。閲覧可否は §8 の記事可視性に従う。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/articles/{id}/revisions` | 記事の改訂履歴一覧（新しい順）。一覧はページング前提（§6 性能） | path: `id`／query: ページング（既定 `Pagination`） | `version`・`title`・`editor`(user)・`created_at` の配列。本文は一覧で返すか §9 未確定 | 401 未認証／403 閲覧権限なし／404 記事なし |
| GET | `/articles/{id}/revisions/{version}` | 単一版の参照（`title`/`body` 全文 snapshot） | path: `id`, `version` | `version`・`title`・`body`・`editor`・`created_at` | 401 未認証／403 閲覧権限なし／404 記事 or 版なし |

- レスポンス整形は `JsonResource`、`ApiResponse::success/notFound/forbidden/unauthenticate` 経由（§6 可観測性、php.md）。
- 例外は `Log::error` ＋ `report()` ＋ `serverError`（§6、php.md コントローラ定型）。

## 6. UI / 画面設計
> 差分表示は UI 側責務（§4.7）。本ドラフトはサーバ機能が主眼のため UI は概略。

- 画面 / 状態:
    - 記事詳細画面内の「改訂履歴」パネル / タブ（版の一覧: 版番号・編集者・日時）。
    - 版選択 → 過去版の内容表示、現行版との **差分ビュー**（diff はフロントで算出・ハイライト）。
- 主要操作:
    - 履歴一覧の取得・ページング（`@/api/article-revisions.ts` 等を `client` 経由で追加。typescript.md）。
    - 版の選択 → 単一版取得 → 現行 `body` と比較表示。
- 入力バリデーション・エラー表示:
    - 読み取り専用機能のため入力は最小（`version` はルートパラメータ）。404/403 を `ApiError` で捕捉しユーザー向け表示（typescript.md）。

## 7. 技術選定と根拠
- 採用:
    - **Append-only な別テーブル `article_revisions`**（§5 で確定済み）に記事更新時スナップショットを追記。
    - **`article-core` の更新ユースケース内で版を生成**（更新と履歴追記を同一トランザクション境界で扱う）。
    - レイヤード縦切り（Input/Output/UseCase/Filter/Result/Repository interface+impl/Controller/Request/Resource）を `Team` 同様に踏襲（§6、CLAUDE.md）。
- 理由:
    - 別テーブル追記は記事本体テーブルを肥大化させず、不変履歴・監査に適する。`(article_id, version)` での参照が単純。
    - 差分をサーバ保存（行単位 patch 等）にしないことで実装単純化。差分表示は UI 側に閉じる（§4.7）→ サーバはストレージと参照に専念。
- 却下した代替案 / 却下理由:
    - **記事本体への version カラムのみ（履歴テーブルなし）**: 過去版を保持できず US-A3 を満たせない。却下。
    - **差分（patch）をサーバ保存し版を再構成**: 実装・整合性コストが高く、§4.7 が差分を UI 責務としているため過剰。却下。
    - **イベントソーシング / 操作単位ログ**: MVP/P2 のスコープに対し過剰（§1.4 YAGNI）。却下。

## 8. エッジケース・非機能
- エッジケース:
    - 記事の**どの更新で版を残すか**（タイトル/本文変更時のみか、status 変更も含むか）→ §9 未確定。
    - 版が 0 件（まだ更新されていない記事 / 作成直後）の一覧 → 空配列を返す。初版（作成時）を version=1 として記録するかは §9 未確定。
    - 同一記事への並行更新時の `version` 採番競合（複合ユニーク制約 + リトライ/採番方式）→ §9 未確定。
    - 存在しない `version` 指定 → 404。
- 認可 / 権限（§8 を踏襲）:
    - 改訂履歴の閲覧可否は**記事本体の閲覧可否に準ずる**のが自然: `published`＋`team` は所属メンバー以上、`published`＋`public` は Guest 含む、`draft/in_review` は著者と Admin のみ（§7.1, §8）。**履歴特有の権限境界は正本に明記なし → §9 未確定**。
    - 書き込み API は無し（履歴は記事更新の副作用として生成）。
- 失敗時の挙動:
    - 版生成は記事更新トランザクションの一部。記事更新が失敗したら版も作られない（整合性維持）。
    - 例外は `Log::error`＋`report()`＋`ApiResponse::serverError()`（§6）。
- 性能・可用性で意識する点:
    - 一覧はページング必須（§6 性能、既定 `Pagination`）。
    - `editor_user_id` → users の eager load で N+1 回避（§6）。
    - `(article_id, version)` インデックスで単一版参照・一覧ソートを高速化。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`（PHPUnit 12、Unit/Feature、行カバレッジ 100%、`#[Test]` 属性、Mockery、Feature は MySQL テスト DB）。
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`（`client` 経由・`api/types.ts` 集約・`ApiError` 捕捉）。
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - 記事更新時の版生成: UseCase テストで `ArticleRevisionRepositoryInterface`（仮）をモックし、更新ごとに append が呼ばれることを検証。
        - Controller catch（例外分岐）: リポジトリモックを `andThrow` で `serverError` 到達（php.md パターン）。
        - 認可分岐: 記事 visibility/status × ロール（Owner/Admin/Member/Guest）で閲覧可否を網羅。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1: 記事更新 → 版が 1 件追記され、`version` が単調増加すること。
        - US2/US3: 一覧（新しい順・ページング）と単一版（`body` 全文）の取得。
        - US4: 存在しない記事 / version で 404。
    - DB / 外部サービス依存で注意する点:
        - マイグレーションは MySQL 専用生 SQL（`UUID_TO_BIN`/`ENUM`/`COMMENT`）で sqlite 不可。Feature は `learning_portal_testing`／`.env.testing` 前提（php.md）。
        - `(article_id, version)` 複合ユニーク制約の検証（重複 version 拒否）。

## 9. 未確定（brainstorming で詰める）
- [ ] **version 採番方式**: 記事ごとに 1 から連番か、`MAX(version)+1` か、タイムスタンプ系か。並行更新時の競合回避（DB ユニーク + リトライ / アプリ採番 / トリガ）をどうするか。
- [ ] **保存トリガの範囲**: どの更新で版を残すか。(a) `title`/`body` 変更時のみ、(b) `status` 遷移（§7.1）も版に含めるか、(c) tags/attachments など関連リソース変更は対象外か。記事作成（初版）を version=1 として記録するか。
- [ ] **差分の UI/サーバ責務境界**: §4.7 は「差分は UI 側」。サーバは生の `title`/`body` snapshot を返すのみで確定か。一覧 API で `body` 全文を返すか（重い）／一覧はメタのみ・単一版で `body` を返すか。
- [ ] **改訂履歴閲覧の認可境界**: 記事本体の可視性（§8）にそのまま準ずるか、`draft/in_review` の履歴は著者・Admin のみ等の特別扱いが要るか。Guest の public 記事履歴閲覧可否。
- [ ] **ロールバック / 復元の要否**: 正本未記載。過去版を現行へ戻す操作を将来 API 化するか（本機能のスコープ外で確定でよいか）。
- [ ] **履歴の保持・削除ポリシー**: 版の `deleted_at`（論理削除）を持つか、記事削除（論理削除）時に履歴をどう扱うか。版数の上限 / 古い版のパージは要るか。
