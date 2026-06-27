# ドラフト: コレクション（学習パス）（collections）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: コレクション（学習パス）（collections）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存: `article-core`（§4.6 記事。コレクションが束ねる項目＝記事の母体。§11「`reactions/bookmarks/comments/revisions` は `article-core` に依存」と同様にコレクションも記事に依存）。
    - 認可の前提: `team-membership`（§11 依存の要点「`article-*` は `team-membership`（認可）に依存」。コレクションも `team_id` に紐づき、所属＋ロールで可視性・編集権を制御）。
    - 所属の母体: `team-core`（§4.3。`collections.team_id` の参照先。`visibility` の team/public 概念を共有）。
    - リリース: Phase 3（§11、14 番目）。MVP/P2 完了後に着手。

## 1. 目的（Why）
散在する記事を、学ぶべき順序を付けて束ねる **学習パス（コレクション）** を提供し、「何を学べばよいか」の順序が示されず新人が迷子になる課題（§1.2）を解消する。Contributor が複数記事を順序付きでキュレーションし（US-L1）、Learner がオンボーディング・自己学習を体系的に進められるようにする（§1.3「タグ・全文検索・コレクション（学習パス）で探せる・順序立てて学べる」）。

## 2. スコープ
- やること:
    - コレクションの作成・編集・公開（`GET /collections`・`POST /collections`・`PUT /collections/{id}`、§9 API）。
    - コレクション項目（`collection_items`）の並べ替え（`PUT /collections/{id}/items`、§9 API。順序＝`position`）。
    - データモデル: `collections`（`team_id`/`owner_user_id`/`title`/`description`/`visibility`）と `collection_items`（`collection_id`/`article_id`/`position` 複合 uniq）（§5.1）。
    - `visibility`: team（チーム内）/ public（全社）の出し分け（§4.13 公開、§5.1）。
- やらないこと（今回対象外）:
    - 記事そのものの作成・編集・状態遷移（`article-core`〔§4.6〕）。本機能は既存記事を参照して束ねるだけ。
    - 全文検索でのコレックション横断検索（`search`〔§4.14, P2〕。本機能はコレクション単位の取得が中心）。
    - 学習進捗トラッキング（どの項目を読了したか）・修了バッジ（§4.16 `gamification` 側。正本のコレクション定義に進捗列なし）。
    - 通知連携（`notifications`〔§4.15〕。§10 の `Notification\Type` にコレクション関連イベントは無い）。
- 非ゴール（誤解されやすいので明示）:
    - コレクションは記事の **複製ではなく参照**。項目を消してもコレクション内のリンクが外れるだけで記事は消えない。
    - 階層コレクション（コレクションの入れ子）・コレクションへのタグ付けは対象外（`collection_items.article_id` は記事のみを指す、§5.1）。
    - コレクション自体のワークフロー（draft→review→published のような状態機械）は持たない。可視性は `visibility`（team/public）のみ（§5.1。記事の `status` のような enum は §10 に無い）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1: Contributor（記事作成権限のあるメンバー）が複数記事を選び、順序付きのコレクション（学習パス）を作成できる（US-L1「複数記事をコレクション（学習パス）に順序付けて束ねられる」）。
- [ ] US2: コレクション所有者が項目を追加・削除・並べ替えして学習パスを編集できる（§4.13「項目の並べ替え」、`PUT /collections/{id}/items`）。
- [ ] US3: 所有者がコレクションのメタ情報（`title`/`description`/`visibility`）を編集し、`visibility=public` で全社へ公開できる（§4.13「公開」）。
- [ ] US4: Learner がコレクション一覧（`GET /collections`）を取得し、可視性・所属に応じて閲覧できる順序付き記事リストとして学べる。

## 4. データモデル設計
- エンティティ:
    - `collections`（学習パス本体。チームに属する）
    - `collection_items`（コレクション内の項目＝記事への順序付き参照）
- 属性 / 型 / 制約（§5.1、§5「全テーブル `id` は `CHAR(36)` UUID、監査列・論理削除方針を踏襲」）:
    - `collections.id`: `CHAR(36)` UUID（PK）
    - `collections.team_id`: FK → `teams.id`（所属チーム。可視性・認可の基準）
    - `collections.owner_user_id`: FK → `users.id`（作成者＝所有者）
    - `collections.title`: 文字列（必須想定。長さ上限は §9）
    - `collections.description`: テキスト（任意）
    - `collections.visibility`: enum `team` / `public`（§5.1。enum 名は §9 で確認＝再利用 or 新規）
    - 監査列: `created_at` / `updated_at`、作成/更新/削除ユーザーID、論理削除対象は `deleted_at`（§5 既存方針。記事が論理削除なのに揃え、§4.13 は明示なし→§9）。
    - `collection_items.id`: `CHAR(36)` UUID（PK）
    - `collection_items.collection_id`: FK → `collections.id`
    - `collection_items.article_id`: FK → `articles.id`
    - `collection_items.position`: 整数（順序）。複合 uniq: (`collection_id`, `position`)（同一コレクション内で順序が一意、§5.1）
- リレーション（§5.2）:
    - `teams 1─* collections 1─* collection_items *─1 articles`
    - `users 1─* collections`（owner_user_id）
- enum:
    - `visibility`（team/public）。§10 では `Article\Visibility`（team/public）が定義済みだが **`Collection\Visibility` の独立 enum 定義は §10 に無い** → 再利用するか新設するかは §9。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須、`ApiResponse` エンベロープ準拠（§9 前文）。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/collections` | コレクション一覧取得（可視性・所属でフィルタ。ページング） | クエリ（`team`・`visibility`・ページング想定。§6 一覧はページング必須） | `collections[]`（`id`/`title`/`description`/`visibility`/`team_id`/`owner` 等） | 401（未認証） |
| POST | `/collections` | コレクション作成 | `team_id`・`title`（必須）・`description`・`visibility`・初期 `items`（採否は §9） | 作成された `collection`（`id` 含む） | 401 / 403（作成権限なし）/ 404（team なし）/ 422（検証） |
| PUT | `/collections/{id}` | コレクションのメタ更新（`title`/`description`/`visibility`） | `title`・`description`・`visibility` | 更新後の `collection` | 401 / 403（所有者/Admin 以外）/ 404 / 422 |
| PUT | `/collections/{id}/items` | 項目集合の置換＋並べ替え（順序確定） | `items[]`（`article_id` の順序付き配列 or `{article_id, position}[]`。形は §9） | 更新後の `collection` ＋ `items[]`（`position` 昇順） | 401 / 403 / 404（collection/article なし）/ 422（重複・他チーム記事等） |

- レスポンスは `{ data, message, result }`（§6 可観測性、CLAUDE.md）。一覧 API はページング既定（`Pagination`、§6 性能）。
- 項目操作は専用エンドポイント（`/items`）にメタ更新（`/{id}`）と分離（§9 API の通り）。個別追加/削除エンドポイント（`POST /items`・`DELETE /items/{itemId}`）は §9 の通り **置換方式に寄せるか分離するか未確定**。

## 6. UI / 画面設計
- 画面 / 状態:
    - コレクション一覧（自分の/チームの/公開コレクション。`GET /collections`）。
    - コレクション詳細（順序付き項目リスト＝学習パス表示。`position` 昇順）。
    - コレクション作成/編集フォーム（`title`/`description`/`visibility` ＋ 記事選択）。
    - 項目並べ替え UI（ドラッグ＆ドロップ等で順序変更 → `PUT /collections/{id}/items`）。
- 主要操作: 記事の追加・除去・並べ替え・保存、`visibility` 切替で公開/限定。
- 入力バリデーション・エラー表示: `title` 必須・長さ上限（具体は §9）、項目 0 件時の扱い（§9）。API 403/422 は `ApiError` 捕捉でフォームへフィードバック（typescript.md）。一覧 API 入出力型は `api/types.ts` に集約、`api/collection.ts` を追加（typescript.md）。

## 7. 技術選定と根拠
- 採用:
    - 別テーブル `collection_items` ＋ `position` 整数列で順序を保持（§5.1 のとおり）。
    - 並べ替えは **集合置換**（`PUT /collections/{id}/items` で項目集合と順序を丸ごと確定）。
    - `(collection_id, position)` 複合 uniq で順序の一意性を DB レベルで保証（§5.1）。
    - 可視性は `team`/`public` の 2 値で記事と同概念（§5.1、§8 の team/public 閲覧制御を踏襲）。
- 理由:
    - §5.1 が `collections`/`collection_items`（position 複合 uniq）を独立テーブルで定義済み。順序を行データで持つことで並べ替え・部分参照が素直。
    - 集合置換は API が冪等で実装・テストが単純（差分の add/move/remove より分岐が少なく、`position` 重複を一括で整合できる）。レビュー実装の縦切り（Team）と同じ Input/Output/UseCase/Filter/Result パターンに乗せやすい。
- 却下した代替案 / 却下理由:
    - `collections` 行に記事 ID 配列（JSON/カンマ区切り）を保持: 複合 uniq・FK・並べ替えクエリが破綻するため却下（§5.1 のテーブル設計に反する）。
    - 連結リスト（各項目が next を持つ）で順序表現: §5.1 が `position` 列を定義済みで過剰設計。YAGNI（§1.4）。
    - 個別 add/remove/move 差分 API: 冪等性・テスト容易性・`position` 整合の単純さで集合置換を優先（最終形は §9）。

## 8. エッジケース・非機能
- エッジケース:
    - `PUT /collections/{id}/items` に空配列 → 全項目クリア（許可するか最低 1 件必須かは §9）。
    - 同一記事を 1 コレクションに重複指定 → `collection_items` に記事重複の uniq 制約は §5.1 に **無い**（複合 uniq は `(collection_id, position)`）。記事の重複登録を許すかは §9。
    - `position` の採番・歯抜け・重複 → 置換時にサーバ側で 0/1 起点の連番へ正規化するか、クライアント指定値を尊重するかは §9。
    - 他チームの記事を項目に混在 → 可否は §9（`collections.team_id` と `articles.team_id` の一致を要求するか）。
    - 参照先記事が論理削除/アーカイブ済み → コレクション項目の見え方（除外/グレーアウト）は §9。
    - 公開（`visibility=public`）コレクションに team 限定記事が含まれる → 公開範囲の不整合（非メンバーに team 記事が漏れないか）。継承・整合ルールは §9。
- 認可 / 権限:
    - §8 の認可マトリクスに **コレクション専用行が無い** → 作成/編集/削除/閲覧に必要なロールは §9 で確定。暫定方針: 作成・編集は所有者（`owner_user_id`）＋当該チームの Owner/Admin、閲覧は `visibility`＋所属に従う（§8 の team/public 記事閲覧ルールを踏襲）。
    - 全 API で Keycloak セッション必須（§6 認可、§9 前文）。`published`＋`public` 記事の閲覧が Guest 可（§8）であることと、public コレクションの Guest 閲覧可否の整合は §9。
- 失敗時の挙動: 例外は `Log::error` ＋ `report()` ＋ `ApiResponse::serverError()` の定型（§6 可観測性、php.md）。
- 性能・可用性で意識する点:
    - `GET /collections`・項目展開はページング＋ eager load で N+1 回避（§6 性能。`collection_items → articles` の結合）。
    - `(collection_id, position)` 複合 uniq とFKにインデックス（§5.1 由来）。
    - 並べ替え置換は 1 トランザクションで `position` 整合を保つ（部分更新で uniq 違反を起こさない）。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - `CollectionRepositoryInterface` をモックして UseCase の正常/異常分岐に到達（Team 縦切りの `ListTeamUseCaseTest` を雛形）。
        - 例外定型（catch → `serverError`）は Repository モックの `andThrow` で到達させる。
        - 認可分岐（所有者 / 同チーム Admin / 一般 Member / 非所属 Guest、team/public 可視性）を §8 の team/public ルールに沿って網羅。
        - 並べ替え置換時の `position` 正規化・`(collection_id, position)` uniq 違反分岐。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1/US2 順序付き束ね＋並べ替え（置換の冪等性、`position` 昇順での出力）。
        - US3 `visibility` 切替（team→public）で閲覧可能範囲が変わること。
        - US4 一覧の可視性・所属フィルタが認可マトリクス通りに効くこと。
    - DB / 外部サービス依存で注意する点:
        - Feature/Repository テストは MySQL テスト DB 前提（`learning_portal_testing` / `.env.testing`）。マイグレーションは MySQL 専用 SQL（`UUID_TO_BIN`/`ENUM`/`COMMENT`）で sqlite 不可（php.md）。
        - `(collection_id, position)` 複合 uniq の制約違反は DB レイヤで起きるため、Repository の Feature テストで実 DB を使って検証する。
        - 行カバレッジ 100% 維持（§12、`task php:coverage-text`）。

## 9. 未確定（brainstorming で詰める）
- [ ] `position` の採番方式: クライアント指定値を尊重するか、サーバが 0/1 起点の連番へ正規化するか。歯抜け・負値・重複入力時の正規化ルール。並べ替えの基準（先頭=0 か 1 か）。
- [ ] 並べ替え/項目更新 API（`PUT /collections/{id}/items`）の入出力形: ① `article_id` の順序付き配列（position はインデックス）か、② `[{article_id, position}]` の明示指定か。レスポンスの項目表現（item id を返すか、記事の埋め込み深さ）。
- [ ] 項目操作を集合置換に一本化するか、個別追加/削除（`POST /collections/{id}/items`・`DELETE .../items/{itemId}`）を別途用意するか（§9 API は `PUT .../items` のみ明記）。
- [ ] 同一記事の重複登録の可否（`collection_items` に `(collection_id, article_id)` の uniq は §5.1 に無い）。学習パスとして重複を許す/禁止するか。
- [ ] 他チーム記事の混在可否: `collections.team_id` と項目記事の `articles.team_id` の一致を強制するか、横断キュレーションを許すか。許す場合の可視性整合。
- [ ] 公開範囲の継承/整合: `visibility=public` コレクションに team 限定（`visibility=team`）記事が含まれるときの扱い（公開を禁止 / 非メンバーには該当項目を隠す / 警告のみ）。public コレクションの Guest 閲覧可否（§8 の public 記事 Guest 可と整合させるか）。
- [ ] 認可マトリクス（§8 にコレクション行なし）: 作成・編集・削除・閲覧に必要なロールの確定（所有者のみ編集か、チーム Admin も編集可か。他者コレクションの編集/削除権）。
- [ ] `visibility` enum の扱い: `Article\Visibility`（team/public）を再利用するか、`Collection\Visibility` を新設するか（§10 に Collection enum 定義なし）。
- [ ] `collections` の削除方式（論理削除 `deleted_at` を持つか）と、削除時の `collection_items` の扱い（カスケード/孤児掃除）。§9 API に DELETE は未記載 → 削除エンドポイントの要否。
- [ ] 項目数 0 件のコレクション（空の学習パス）を許容するか、最低 1 件必須か。`title`/`description` の長さ・必須バリデーション。
- [ ] 参照先記事が論理削除/`archived` の場合のコレクション内表示（除外 / プレースホルダ表示 / 自動デタッチ）。
- [ ] `GET /collections` のフィルタ・ソート仕様（自分の/チームの/公開、ソートキー、クエリパラメータ名）。
