# ドラフト: リアクション（article-reactions）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: リアクション（article-reactions）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存: `article-core`（§4.6 記事。リアクション対象＝公開記事。§11「`reactions/bookmarks/comments/revisions` は `article-core` に依存」）
    - 認可の前提: `team-membership`（§11 依存の要点「`article-*` は `team-membership`（認可）に依存」）
    - 関連: `notifications`〔P3, §4.15〕（`reaction_added` を被リアクション通知へ。本機能は通知イベントの発火元。§10 `Notification\Type` に `reaction_added`）
    - 関連: `gamification`〔P3, §4.16〕（被リアクションを貢献度ポイントへ集計。§1.3-5, US-G1）。本機能は集計元データの提供側
    - 並走: `bookmarks`〔P2, §4.12〕（記事×ユーザーのトグル系として構造が類似だが別機能・別テーブル）

## 1. 目的（Why）
記事への軽量な評価（`like` / `helpful` / `insightful`）をワンクリックのトグルで付与・解除できるようにし、知識を磨き合う反応サイクルを作る（§1.3-4「コメント・リアクション・ブックマークで知識を磨き合う」、US-C2）。集計表示で記事の有用性を可視化し、被リアクションは将来の貢献度（§4.16）の入力になる。

## 2. スコープ
- やること:
    - 記事へのリアクション付与（トグル ON）: `POST /articles/{id}/reactions`（§9 リアクション行）。
    - 記事へのリアクション解除（トグル OFF）: `DELETE /articles/{id}/reactions/{kind}`（§9 リアクション行）。
    - 種別は `like` / `helpful` / `insightful` の 3 種（§10 `Reaction\Kind`）。記事×ユーザー×種別でユニーク（§4.11, §5.1 `reactions`）。
    - 集計表示（種別ごとの件数 ＋ 自分が付けたか）。記事詳細/一覧で参照（§4.11「集計表示」）。
    - データモデル: `reactions`（`article_id` / `user_id` / `kind`、`article×user×kind` uniq、§5.1）。
- やらないこと（今回対象外）:
    - 通知配信（`notifications`〔P3〕）。本機能は `reaction_added` イベントの発火点を用意するに留め、配信実装は §4.15 側（§11）。
    - 貢献度ポイントの加算ロジック（`gamification`〔P3, §4.16〕）。集計の取り込みは別機能。
    - コメントへのリアクション（正本は記事に対してのみ。`reactions.article_id`、§5.1）。
    - 絵文字リアクションの自由追加・カスタム種別（種別は enum 3 値で固定、§10。YAGNI §1.4）。
- 非ゴール（誤解されやすいので明示）:
    - リアクションは記事に対してのみ付く。コメント/ユーザー/チーム/コレクションへの付与は対象外。
    - 1 ユーザーが同一記事に複数種別を同時に付けるのは可（種別ごとに別レコード、`article×user×kind` uniq）。種別間の排他（like を付けたら helpful が外れる等）は無い。

## 3. ユースケース / ユーザーストーリー
- [ ] US1: Member（閲覧権限のあるユーザー）が公開記事に `like`/`helpful`/`insightful` を付けられる（US-C2、§8「コメント・リアクション・ブックマーク」）。
- [ ] US2: 同じ記事・同じ種別にもう一度操作すると解除される（トグル。`DELETE .../reactions/{kind}`）。重複付与は uniq で防がれ、冪等に振る舞う（§4.11）。
- [ ] US3: ユーザーが記事を開くと、種別ごとの合計件数と「自分が付けた種別」が分かる（集計表示、§4.11）。
- [ ] US4: Guest（未所属の認証ユーザー）は **public 記事に限り** リアクションできる（§8「コメント・リアクション・ブックマーク」列の「（public 記事のみ）」）。

## 4. データモデル設計
- エンティティ:
    - `reactions`（記事×ユーザー×種別のリアクション）
- 属性 / 型 / 制約（§5.1、§5「全テーブル `id` は `CHAR(36)` UUID、監査列・論理削除方針を踏襲」）:
    - `reactions.id`: `CHAR(36)` UUID（PK）
    - `reactions.article_id`: FK → `articles.id`（対象記事）
    - `reactions.user_id`: FK → `users.id`（付与者）
    - `reactions.kind`: enum `like` / `helpful` / `insightful`（§10 `Reaction\Kind`、MySQL `ENUM` 列）
    - 複合 uniq: (`article_id`, `user_id`, `kind`)（同一ユーザーが同一記事に同一種別を二重付与できない、§4.11/§5.1）
    - 監査列: `created_at` / `updated_at`、作成/更新/削除ユーザーID（§5 既存方針）。論理削除（`deleted_at`）を持たせるかは §9（トグル解除を物理 delete とするか論理削除とするかに依存）。
- リレーション:
    - `articles 1─* reactions`（§5.2「articles 1─* … reactions …」）
    - `users 1─* reactions`（付与者。`reactions.user_id`）
- enum:
    - `Reaction\Kind`: `like` / `helpful` / `insightful`（§10）。バック付き enum で表現（php.md）。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須、`ApiResponse` エンベロープ準拠（§9 前文）。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| POST | `/articles/{id}/reactions` | リアクション付与（トグル ON） | `kind`（必須・`like`/`helpful`/`insightful`） | 付与後の集計（種別ごと件数＋自分の付与状態） | 401（未認証）/ 403（閲覧/反応権限なし）/ 404（記事なし・非公開）/ 422（不正 kind） |
| DELETE | `/articles/{id}/reactions/{kind}` | リアクション解除（トグル OFF） | パスの `kind` | 解除後の集計 | 401 / 403 / 404（記事 or 当該リアクションなし）/ 422（不正 kind） |

- レスポンスは `{ data, message, result }`（§6 可観測性、CLAUDE.md）。集計は種別ごとの件数 ＋ `my_reactions`（自分が付けた種別の集合）を返す想定（形は §9）。
- `POST` の冪等性: 既に同一 (`article`,`user`,`kind`) がある場合の扱い（再付与を 200 で no-op とするか 409 か）は §9。

## 6. UI / 画面設計
> 記事 UI（`article-core`）に内包される。リアクション単独の画面は持たない想定。
- 画面 / 状態:
    - 記事詳細の **リアクションバー**（3 種ボタン。各ボタンに件数バッジ＋自分の付与状態のトグル表示）。
    - 記事一覧カードの **集計表示**（件数のみの軽量表示。一覧で個別トグルを出すかは §9）。
- 主要操作:
    - 未付与ボタンを押す → `POST /articles/{id}/reactions`（楽観的更新で即トグル）。
    - 付与済みボタンを押す → `DELETE /articles/{id}/reactions/{kind}`。
    - レスポンスの集計で UI を確定（サーバ集計を正とする）。
- 入力バリデーション・エラー表示:
    - `kind` はフロントの enum 定数に限定（不正値は送らない）。API 422/403 は `ApiError` 捕捉でトースト等にフィードバックし、楽観的更新をロールバック（typescript.md）。

## 7. 技術選定と根拠
- 採用:
    - 種別ごとに 1 レコードの `reactions` テーブル ＋ (`article_id`,`user_id`,`kind`) 複合 uniq。
    - トグルは「存在すれば解除、無ければ作成」。POST=付与、DELETE=解除の素直な対応。
    - 集計は記事ごとに `kind` で `GROUP BY COUNT`（記事詳細で算出）。`article-core` の記事取得時に eager / 集計同梱できる形にする。
- 理由:
    - §5.1 が `reactions` を独立テーブル＋`article×user×kind` uniq で定義済み。種別ごと別レコードにより「複数種別の同時付与」と「種別単位のトグル」が構造的に自然に表現できる（§2 非ゴール参照）。
    - uniq 制約で重複付与を DB レイヤで防止でき、トグルの冪等性をテストしやすい（§4.11）。
    - DELETE をパスの `{kind}` で受ける設計（§9）に集合操作が要らず、RESTful で実装・テストが単純。
- 却下した代替案 / 却下理由:
    - 記事行に `like_count` 等のカウンタ列を持ち増減: 整合性（多重カウント・誰が付けたか不明）が崩れ、「自分が付けたか」を出せない。§5.1 の `reactions` テーブル設計に反するため却下。集計はクエリ側で算出する。
    - 1 ユーザー 1 記事 1 リアクション（種別をレコード更新で切替）: §10 が 3 種を独立に扱い、§2 で種別間排他なしと整理。複数種別同時付与を許すため却下。
    - リアクション専用の集計 API を別途用意: 記事取得に集計を同梱すれば往復が減る。専用 API の要否は §9。

## 8. エッジケース・非機能
- エッジケース:
    - 既に付与済みの種別へ再 `POST` → uniq 違反。no-op 200 で現状集計を返すか 409 かは §9（冪等寄りを推奨）。
    - 未付与の種別へ `DELETE` → 対象なし。冪等に 200/204 とするか 404 かは §9。
    - 不正な `kind`（enum 外）→ 422（`Reaction\Kind::tryFrom` で弾く、php.md「検証後の値だけ扱う」）。
    - 非公開/下書き/レビュー中記事へのリアクション → 閲覧不可なので 403/404（§8「`published` のみ閲覧可」「`draft/in_review` は著者と Admin のみ」）。published のみ反応可とするかは §9。
    - 自分の記事への自己リアクション可否 → 正本に明記なし（§9）。
    - 記事削除（論理削除）時の付随リアクションの扱い（残置 or カスケード）→ §9。
- 認可 / 権限（§8）:
    - リアクションは「コメント・リアクション・ブックマーク」権限に従う。Owner/Admin/Member ✓、Guest は **public 記事のみ** ✓（§8 マトリクス）。
    - team 公開記事は所属メンバーのみ反応可（§8「`published`＋`team` 記事の閲覧」が Member 以上）。Guest は team 記事に反応不可。
    - 全 API で Keycloak セッション必須（§6 認可）。
- 失敗時の挙動: 例外は `Log::error` ＋ `report()` ＋ `ApiResponse::serverError()` の定型（§6 可観測性、php.md）。
- 性能・可用性で意識する点:
    - 集計は (`article_id`,`kind`) のインデックス前提で `COUNT`。一覧で多記事の集計を出す場合 N+1 を避ける（Infra で一括集計 / eager、§6 性能）。
    - 複合 uniq (`article_id`,`user_id`,`kind`) のインデックスで重複付与チェックと「自分の付与状態」取得を高速化。
    - 通知（`reaction_added`）は Queue 非同期前提（§4.15/§6 可用性）。本機能ではイベント発火点のみで配信は P3。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - トグルの「存在 → 解除」「不在 → 作成」両分岐を UseCase で双方到達（`ReactionRepositoryInterface` をモック）。
        - 例外定型（catch → `serverError`）は Repository モックの `andThrow` で到達させる。
        - 認可分岐（Member / Guest×public / Guest×team / 非公開記事）を §8 マトリクス通りに到達させる（ファサードは `Auth::shouldReceive(...)`、Controller catch は `$this->app->instance(...)`）。
        - 不正 `kind` の 422 分岐（FormRequest / パス制約で `Reaction\Kind::tryFrom` 失敗）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US2 トグル冪等性: 同一 (`article`,`user`,`kind`) への再付与・未付与解除が冪等に振る舞う。
        - 複合 uniq (`article×user×kind`) の制約違反分岐（DB レイヤで起きる一意制約違反を Repository が握って no-op/集計返却にする経路）。
        - US3 集計の正しさ（種別ごとの件数 ＋ `my_reactions`）。複数ユーザー・複数種別の集計を Feature で検証。
        - US4 Guest×public 記事は可、Guest×team 記事は不可（§8）。
    - DB / 外部サービス依存で注意する点:
        - Feature/Repository テストは MySQL テスト DB 前提（`learning_portal_testing` / `.env.testing`）。マイグレーションは MySQL 専用 SQL（`UUID_TO_BIN` / `ENUM` / `COMMENT`）で sqlite 不可（php.md）。
        - `article×user×kind` 複合 uniq 違反は DB レイヤで起きるため、Repository の Feature テストで実 DB を使って検証する。

## 9. 未確定（brainstorming で詰める）
- [ ] 集計レスポンスの形: 件数の返し方（`counts: {like, helpful, insightful}` か配列か）と、自分の付与状態（`my_reactions: kind[]`）の表現。記事取得（`article-core`）に同梱するか、専用集計エンドポイントを持つか。
- [ ] `POST` で既付与時の HTTP セマンティクス: no-op 200（現状集計返却）/ 201 / 409 のいずれか（冪等寄り推奨だが要確定）。
- [ ] `DELETE` で未付与時のセマンティクス: 冪等 200/204 か 404 か。
- [ ] 解除の実装: 物理 delete か論理削除（`deleted_at`）か。§5 監査・論理削除方針を `reactions` に適用するか（再付与時の挙動にも影響）。
- [ ] published のみ反応可か（`draft/in_review/archived` への反応可否）。§8 は閲覧可否を定義するが反応のゲートを明文化していない。
- [ ] 自己記事へのリアクション可否（著者が自分の記事に付けられるか）。貢献度（§4.16）の不正稼ぎ防止観点も含めて要判断。
- [ ] team 記事への Guest 不可・public 記事のみ Guest 可、の認可をどの層でゲートするか（FormRequest / UseCase / Policy 相当）。`article-core` の可視性判定との責務分担。
- [ ] 記事の論理削除時に付随 `reactions` をどう扱うか（残置 / カスケード論理削除 / 集計から除外）。
- [ ] 通知連携（`reaction_added`）の発火タイミングと、自己リアクション時に通知を抑制するか（P3 `notifications` との境界）。
- [ ] 一覧（記事カード）で集計を出す範囲: 件数のみか、自分の付与状態も含めるか（N+1 / 一括集計のクエリ設計に影響）。
