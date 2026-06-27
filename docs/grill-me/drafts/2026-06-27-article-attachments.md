# ドラフト: 添付ファイル（article-attachments）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 添付ファイル（article-attachments）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存: `article-core`（§4.6 記事。添付の親リソース。`articles.id` に紐づく）
    - 依存（インフラ）: `FileStorageInterface`（`app/Domains/Services/FileStorageInterface.php` ポート）＋ `Infra/External/Aws/S3`（`app/Infra/External/Aws/S3.php` 実装）。§4.9/§11「既存 `FileStorageInterface` を利用」。
    - 認可の前提: `team-membership`（§11 依存の要点「`article-*` は `team-membership`（認可）に依存」）
    - 関連: `article-revisions`（P2。本文の版管理。添付は版管理対象に含めない想定。§9 で要確認）

## 1. 目的（Why）
記事（ナレッジ）に図表・資料・手順書等のファイルを添付し、本文と一体で参照できるようにする（§4.9、US-A1「下書きとして書き、**添付**・タグを付け…公開できる」）。アップロード・一覧・削除を提供し、保存は **既存の S3 ストレージ（`FileStorageInterface` ポート経由）**、ダウンロードは **署名付き URL** とすることで、ストレージ実体への直アクセスを避けつつ安全に配布する（§6 ファイル「添付は S3（`FileStorageInterface` ポート経由）。署名付き URL でダウンロード」）。MIME ホワイトリスト＋サイズ上限により不正・過大ファイルを排除する（§6 セキュリティ）。

## 2. スコープ
- やること:
    - 記事への添付アップロード（`POST /articles/{id}/attachments`、§9 API）。
    - 添付一覧の取得（`GET /articles/{id}/attachments`、§9 API）。ダウンロード用の署名付き URL を含めて返す想定。
    - 添付の削除（`DELETE /articles/{id}/attachments/{att_id}`、§9 API）。
    - データモデル: `article_attachments`（`article_id`/`file_path`/`file_name`/`mime_type`/`file_size`、§5.1）。
    - 保存・取得・削除・署名 URL 生成は **`FileStorageInterface`** 経由（`put` / `delete` / `temporaryUrl` を利用。`get`/`exists` は必要に応じ）。実体は `Infra/External/Aws/S3`。
    - バリデーション: MIME ホワイトリスト＋サイズ上限（既定 10MB、§6 セキュリティ）。
- やらないこと（今回対象外）:
    - 画像のサムネイル生成・リサイズ・変換・プレビュー描画（YAGNI §1.4。正本に記載なし）。
    - 添付のバージョニング・差し替え履歴（`article-revisions` は本文版管理。添付は対象外、§9 で確認）。
    - ウイルススキャン・コンテンツ検査（正本に明記なし。§9 で要確認）。
    - 記事本文（Markdown）内インライン画像の埋め込み構文連携（本機能は添付メタの CRUD に限定。§9）。
    - チャンク/再開可能アップロード・ダイレクト（presigned PUT）アップロード（MVP は API 経由アップロードを想定。§9 で方式確認）。
- 非ゴール（誤解されやすいので明示）:
    - 添付はあくまで **記事に紐づく**。チーム/コメント/プロフィール等への汎用添付ではない（`article_attachments.article_id` 固定、§5.1）。
    - ダウンロードは署名付き URL 経由であり、S3 バケットの公開（public-read）ではない（§6 ファイル）。
    - 本機能は新規ストレージ実装を作らない。**既存 `FileStorageInterface`／`S3` アダプタを利用**する（§4.9）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1: Contributor（記事の作成・編集権限を持つメンバー）が自分の記事に資料ファイルを添付できる（US-A1）。
- [ ] US2: 記事の閲覧権限を持つユーザーが、その記事の添付一覧を取得し、署名付き URL でダウンロードできる（§6 ファイル、§8 閲覧権限に従う）。
- [ ] US3: 記事の編集権限を持つユーザーが、不要になった添付を削除できる（§8「自分の記事編集」/「他者記事の編集・削除」）。
- [ ] US4: 許可されていない MIME・サイズ上限超過のファイルはアップロードが拒否される（§6 セキュリティ）。

## 4. データモデル設計
- エンティティ:
    - `article_attachments`（記事の添付メタ。実体ファイルは S3、本テーブルは参照情報を保持）
- 属性 / 型 / 制約（§5.1、§5「全テーブル `id` は `CHAR(36)` UUID、監査列・論理削除方針を踏襲」）:
    - `article_attachments.id`: `CHAR(36)` UUID（PK）
    - `article_attachments.article_id`: `CHAR(36)` FK → `articles.id`（親記事。記事削除時の連動は §9）
    - `article_attachments.file_path`: 文字列（S3 キー＝`FileStorageInterface::put` の戻り値。命名規則は §9）
    - `article_attachments.file_name`: 文字列（アップロード時の元ファイル名＝表示用）
    - `article_attachments.mime_type`: 文字列（検証済み MIME。ホワイトリスト内、§6）
    - `article_attachments.file_size`: 数値（バイト。上限 既定 10MB、§6）
    - 監査列: `created_at` / `updated_at`、作成/更新/削除ユーザーID（§5 既存方針）。論理削除（`deleted_at`）を持たせるかは §9。
- リレーション:
    - `articles 1─* article_attachments`（§5.2「articles 1─* … / article_attachments / …」）。
    - enum: 添付機能に区分値 enum は無し（§10 に attachment enum の定義なし）。MIME ホワイトリストを enum 化するかは §9。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須、`ApiResponse` エンベロープ（`{ data, message, result }`）準拠（§9 前文、§6 可観測性）。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/articles/{id}/attachments` | 記事の添付一覧（署名付き URL 付き） | パス `id`（記事）。ページング想定（§6 一覧はページング必須） | `attachments[]`（`id`/`file_name`/`mime_type`/`file_size`/`download_url`＝署名付き URL/`created_at`） | 401（未認証）/ 403（閲覧権限なし）/ 404（記事なし） |
| POST | `/articles/{id}/attachments` | 記事へファイルを添付 | `multipart/form-data`：`file`（必須）。MIME ホワイトリスト＋サイズ上限（既定10MB） | 作成された `attachment`（`id`/`file_name`/`mime_type`/`file_size`/`download_url`） | 401 / 403（記事編集権限なし）/ 404（記事なし）/ 422（MIME 不可・サイズ超過・必須欠落）/ 413（過大。422 で扱うかは §9） |
| DELETE | `/articles/{id}/attachments/{att_id}` | 添付の削除（S3 実体も削除） | パス `id`（記事）・`att_id`（添付） | 削除結果（`message`。本文なし or `{ id }`） | 401 / 403（編集権限なし）/ 404（記事/添付なし） |

- 一覧はページング既定（`Pagination`、§6 性能）。署名付き URL は `FileStorageInterface::temporaryUrl($path, $minutes)` で都度生成（保存しない）。有効期限分数は §9。
- アップロードは書き込み系のため CSRF トークン必須（§6 セキュリティ、frontend `client`）。

## 6. UI / 画面設計
> 記事 UI（`article-core`）に内包される。添付単独の画面は持たない想定。
- 画面 / 状態:
    - 記事作成/編集フォーム内の **添付エリア**（ファイル選択 → アップロード、添付済み一覧、各添付の削除ボタン）。
    - 記事詳細の **添付一覧表示**（ファイル名・サイズ・MIME アイコン、クリックで署名付き URL ダウンロード）。
    - 状態: アップロード中（プログレス）/ 成功 / 失敗（拒否理由表示）/ 空（添付なし）。
- 主要操作: ファイル選択 → `POST /articles/{id}/attachments`、削除 → `DELETE …/{att_id}`、一覧再取得 → `GET …/attachments`。
- 入力バリデーション・エラー表示:
    - クライアント側で MIME・サイズを事前チェックし、超過/不可は送信前に弾く（最終判定はサーバ）。
    - サーバ 422（MIME 不可・サイズ超過）は `ApiError` 捕捉でファイル単位にフィードバック（typescript.md）。
    - 署名付き URL は期限切れの可能性があるため、ダウンロード時に最新の一覧/URL を取得する運用も検討（§9）。

## 7. 技術選定と根拠
- 採用:
    - メタは `article_attachments`（RDB）、実体は S3（`FileStorageInterface` ポート経由）。ダウンロードは `temporaryUrl` の署名付き URL。
    - アップロードは API（`multipart/form-data`）でサーバ受け→ MIME/サイズ検証 → `FileStorageInterface::put` で保存。
    - 削除は DB レコード削除＋ `FileStorageInterface::delete` で S3 実体も削除（孤児オブジェクト防止）。
- 理由:
    - §6 ファイルが「S3（`FileStorageInterface` 経由）・署名付き URL」を明示。既存ポート/アダプタ（`put`/`delete`/`temporaryUrl`）がそのまま要件を満たす。
    - ポート経由とすることで Application/Domain は S3 非依存に保て、依存方向（§6 アーキテクチャ、php.md）を守れる。AWS 用語は `Infra/External/Aws/S3` に閉じる。
    - サーバ経由アップロードは MIME/サイズ検証をサーバで強制でき（§6 セキュリティ）、CSRF/セッション境界（§6 認可）も既存 `client` に乗る。
- 却下した代替案 / 却下理由:
    - ファイル実体を DB（BLOB）に保存: §6 が S3 を指定。スケール・コストで却下。
    - S3 バケットを public-read で直配布: §6「署名付き URL でダウンロード」に反する。情報漏えいリスクで却下。
    - クライアントから S3 へ直接 presigned PUT アップロード: MIME/サイズのサーバ強制が弱まる。MVP は API 経由を優先（方式は §9 で再検討余地）。
    - 添付ポートを `S3Interface` 等ベンダー名で新設: 能力命名（`FileStorageInterface`）の既存ポートを利用する方針に反する（php.md「ベンダー名で命名しない」）。

## 8. エッジケース・非機能
- エッジケース:
    - サイズ上限（既定 10MB）超過 → 422（または 413、§9）で拒否、S3 へは保存しない。
    - MIME ホワイトリスト外 → 422 で拒否。拡張子偽装に備え、宣言 MIME だけでなく実内容からの判定有無は §9。
    - 同名ファイルの重複アップロード → S3 キーをユニーク化（UUID 等）して上書き衝突を避ける（キー命名は §9）。
    - 親記事が存在しない / 削除済み → 404。論理削除済み記事への添付可否は §9。
    - 添付削除時に S3 実体が既に無い → `delete` を冪等に扱う（例外にしない方針か、§9）。DB 削除と S3 削除の整合（片方失敗時の扱い）は §9。
    - 署名付き URL の期限切れ → 一覧再取得で再発行。
- 認可 / 権限（§8）:
    - アップロード/削除は「記事の作成・自分の記事編集」に従う（Owner/Admin/Member ✓、Guest ✗）。他者記事の添付編集・削除は Owner/Admin のみ（§8「他者記事の編集・削除」）。
    - 一覧/ダウンロードは記事の閲覧権限に従う（`published`＋`team` はメンバー以上、`published`＋`public` は Guest 含む。`draft/in_review` は著者・Admin のみ、§7.1/§8）。
    - 全 API で Keycloak セッション必須（§6 認可、`api/*`）。
- 失敗時の挙動: 例外は `Log::error` ＋ `report()` ＋ `ApiResponse::serverError()` の定型（§6 可観測性、php.md）。S3 障害時もこの定型に乗せる（`FileStorageInterface` の `put`/`delete`/`temporaryUrl` が投げる例外を catch）。
- 性能・可用性で意識する点:
    - 一覧は N+1 を避け（Infra で eager load、§6 性能）、ページング必須（§6）。
    - 署名付き URL は都度生成（保存しない）。一覧 N 件で N 回の URL 生成コストに留意（§9 で一括/遅延生成を検討余地）。
    - 大きいファイルのアップロードはタイムアウト・メモリに注意（§9 で方式・上限再確認）。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - **`FileStorageInterface` をモック差し替え**（`Mockery::mock(FileStorageInterface::class)`）して、実 S3 に触れず UseCase の `put`/`delete`/`temporaryUrl` 呼び出しと戻り値ハンドリングを検証する。`AppServiceProvider` のバインド（`FileStorageInterface` → `S3`）を Controller テストでは `$this->app->instance(...)` で差し替える。
        - S3 障害分岐（catch → `serverError`）は `FileStorageInterface` モックの `andThrow` で到達させる。
        - MIME ホワイトリスト不可・サイズ超過の 422 分岐は FormRequest 単体テストと Feature テスト（`UploadedFile::fake()` で擬似ファイル）で網羅。
        - 認可分岐（自記事 / 他者記事 / Guest / 閲覧権限）は §8 マトリクス通りに到達させる。
        - `Infra/External/Aws/S3` 実装自体のテストは `S3Client` を注入（コンストラクタの `?S3Client`）してモックし、`putObject`/`deleteObject`/`createPresignedRequest` 呼び出しを検証（既存アダプタ流儀）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1 アップロード成功（メタ永続化＋ `put` 呼び出し＋ S3 キー保存）。
        - US2 一覧取得が署名付き URL（`temporaryUrl`）を含めて返す。
        - US3 削除で DB レコードと S3 実体（`delete`）の双方が消える。
        - US4 MIME 不可・サイズ超過の拒否（境界値：上限ちょうど/上限+1）。
    - DB / 外部サービス依存で注意する点:
        - Feature/Repository テストは MySQL テスト DB 前提（`learning_portal_testing` / `.env.testing`）。マイグレーションは MySQL 専用 SQL（`UUID_TO_BIN` 等）で sqlite 不可（php.md）。
        - S3 は **必ずモック**（`FileStorageInterface`）。テストで実 AWS/LocalStack に依存させない（行カバレッジ 100% を安定維持、§6 テスト）。
        - ファイルアップロードは `Illuminate\Http\UploadedFile::fake()` を用い、実ファイル I/O を避ける。

## 9. 未確定（brainstorming で詰める）
- [ ] **許可 MIME ホワイトリストの確定**: 具体的な許可タイプ（画像 png/jpeg/gif、PDF、テキスト、Office 形式 等）。enum 化するか設定（config）化するか。宣言 MIME だけで判定するか、実内容（finfo 等）からも検証するか。
- [ ] **サイズ上限の最終値**: 既定 10MB（§6）を採用で確定か、ファイル種別ごとに変えるか。超過時の HTTP ステータス（422 か 413 か）。
- [ ] **S3 キー（`file_path`）命名規則**: プレフィックス構成（例 `articles/{article_id}/{uuid}/{file_name}` 等）、衝突回避（UUID 付与）、元ファイル名のサニタイズ・パストラバーサル対策。
- [ ] **署名付き URL の有効期限**: `temporaryUrl($path, $minutes)` の `minutes`（既定 10 分）を何分にするか。一覧で全件分を都度生成するか、ダウンロード専用エンドポイントで都度発行するか。
- [ ] **物理削除の有無 / 整合**: `article_attachments` を論理削除（`deleted_at`）にするか物理削除か。DB 削除と S3 `delete` の順序・片方失敗時の扱い（孤児オブジェクト/孤児レコード）。S3 実体が既に無い場合の冪等性。
- [ ] **記事削除時の添付連動**: 記事（論理削除）に伴い添付・S3 実体をどう扱うか（残す/カスケード削除/遅延クリーンアップ）。論理削除済み記事への添付追加可否。
- [ ] **アップロード方式**: API 経由（`multipart/form-data`）で確定か、将来 presigned PUT 直アップロードを許容するか。チャンク/再開可能アップロードの要否。
- [ ] **1 記事あたりの添付上限数 / 合計容量**（正本に明記なし）。
- [ ] **一覧のレスポンス項目**: `download_url`（署名付き URL）をレスポンスに常時含めるか、別途ダウンロードエンドポイントにするか。`file_path`（S3 キー）をクライアントへ露出しない方針の確認。
- [ ] **ウイルススキャン / コンテンツ検査**の要否（MVP 範囲外で確定か）。
- [ ] **記事本文（Markdown）内インライン画像**との連携（添付を本文から参照する仕組みの要否）。`article-revisions`（P2）で添付を版管理対象に含めるか。
- [ ] **添付に対する認可の細部**: 一覧/ダウンロードを記事閲覧権限と完全一致させるか、`draft/in_review` 記事の添付を著者・Admin のみに制限する具体ルール（§7.1/§8 との突き合わせ）。
