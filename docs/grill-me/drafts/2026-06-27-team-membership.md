# ドラフト: チームメンバー管理（team-membership）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: チームメンバー管理（team-membership）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存: `team-core`（§4.3。`teams` / `owner_user_id` / 作成者は自動 `owner`）
    - 後続が依存: `article-*` 全般（§11「`article-*` は `team-membership`（認可）に依存」）= 記事系の認可土台
    - 関連: `team-access`（§4.5 参加申請/招待。承認・承諾で `team_members` に追加。本機能とは別スライス・P2）

## 1. 目的（Why）
チームの所属とロール（`owner` / `admin` / `member`）を管理し、記事系を含む全ドメインの**認可の土台**を提供する（要件 §4.4 / §8 / §11）。
- チーム運用者（Owner / Admin）がメンバー構成と権限を維持できるようにする。
- メンバー本人が自発的に脱退できるようにする。
- **オーナー不在（owner 0 人）を構造的に禁止**し、チームの統治者が常に 1 人以上いる状態を保証する（§4.4）。

## 2. スコープ
- やること:
    - メンバー一覧取得（`GET /teams/{id}/members`）。
    - メンバーのロール変更（`PUT /teams/{id}/members/{user_id}`）— `owner` / `admin` / `member` 間の変更。
    - メンバーの除名 / 脱退（`DELETE /teams/{id}/members/{user_id}`）。
    - **最後の `owner` の降格・脱退（除名含む）禁止**ガード（§4.4）。
    - 上記操作の認可（§8 のロール×権限マトリクスに準拠）。
- やらないこと（今回対象外）:
    - メンバーの**追加**（参加申請の承認 / 招待の承諾）→ `team-access`（§4.5, P2）の責務。本機能は「すでに所属するメンバー」の参照・更新・削除のみ。
    - チーム自体の作成・更新・削除 → `team-core`（§4.3）。
    - 除名・脱退したユーザーが書いた記事の後処理（再割当・非公開化等）→ `article-core` 側で扱う（本機能は `team_members` 行のみ操作）。
- 非ゴール（誤解されやすいので明示）:
    - 細粒度のカスタム権限（ロールの動的定義）— ロールは固定 enum 3 値のみ（§10 `Team\MemberRole`）。
    - 組織横断の権限（Owner ペルソナの全体統括 §2）は本機能の API では扱わない（チーム単位スコープ）。
    - 招待メール送信・通知 — `team-access` / `notifications`（P2/P3）。

## 3. ユースケース / ユーザーストーリー
（正本 US-T3 を中核に分解）
- [ ] US1: Team Admin（または Owner）はチームのメンバー一覧を取得し、各メンバーのロール・参加日時を確認できる。
- [ ] US2: Team Admin（または Owner）はメンバーのロールを `admin` / `member` に変更できる（US-T3）。
- [ ] US3: Team Admin（または Owner）はメンバーを除名できる（US-T3）。
- [ ] US4: メンバー本人は自分の所属を脱退できる。
- [ ] US5: 最後の `owner` をロール変更で降格しようとすると拒否される（オーナー不在の防止 / §4.4）。
- [ ] US6: 最後の `owner` を除名・脱退しようとすると拒否される（同上）。
- [ ] US7: 権限を持たない `member` / `Guest` がロール変更・除名を試みると拒否される（§8）。

## 4. データモデル設計
（正本 §5.1 `team_members` 行・§5.2 関連・§10 enum を転記。本機能は新規テーブルを作らず既存定義を利用）
- エンティティ: `team_members`（所属・ロール）
- 属性 / 型 / 制約:
    - `id`: `CHAR(36)` UUID（§5 全テーブル共通方針）
    - `team_id`: `CHAR(36)`（FK → `teams.id`）
    - `user_id`: `CHAR(36)`（FK → `users.id`）
    - `role`: enum `Team\MemberRole` = `owner` / `admin` / `member`（§10）
    - `joined_at`: 参加日時
    - 監査列: `created_at` / `updated_at`（+ 作成/更新ユーザーID。論理削除対象なら `deleted_at`。§5 既存方針に従う）
    - **複合ユニーク制約: `(team_id, user_id)`**（§5.1「team×user uniq」）
- リレーション（§5.2）:
    - `users 1─* team_members *─1 teams`
    - `teams.owner_user_id`（§5.1）は `team-core` が保持。**本機能のロール変更で `owner` を変更する際、`teams.owner_user_id` と `team_members.role=owner` の整合をどう取るかは §9 未確定**。

## 5. API 設計
`prefix: /api/v1`、Keycloak セッション必須（§9）。`ApiResponse` エンベロープ（`data/message/result`）準拠。

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| GET | `/teams/{id}/members` | メンバー一覧（ページング） | path: `id`（team）／query: `page`/`limit`/`sort`（既定 `Pagination`、§6） | `data`: メンバー配列（`user_id`・`display_name`・`avatar_url`・`role`・`joined_at`） | 401 未認証 / 403 非メンバー（private 時）/ 404 team 不在 |
| PUT | `/teams/{id}/members/{user_id}` | ロール変更 | path: `id`・`user_id`／body: `role`（`owner`/`admin`/`member`） | `data`: 更新後メンバー | 400 バリデーション / 401 / 403 権限不足 / 404 member 不在 / 409 最後の owner 降格禁止 |
| DELETE | `/teams/{id}/members/{user_id}` | 除名 / 脱退 | path: `id`・`user_id` | 204 / 成功エンベロープ | 401 / 403 権限不足 / 404 member 不在 / 409 最後の owner 除名・脱退禁止 |

- ロール変更・除名の認可: **Owner / Admin のみ可**（§8「メンバーのロール変更・除名」= Owner ✓ Admin ✓ Member — Guest —）。
- 脱退（DELETE で `user_id` == 認証ユーザー）は本人なら可（§4.4「脱退」）。**除名と脱退を同一エンドポイントで分岐するか・別経路にするかは §9 未確定**。
- エラーは `ApiResponse`（`badRequest`/`unauthenticate`/`forbidden`/`notFound`/`serverError`）経由（§6 / php.md）。**「最後の owner 禁止」を 409 とするか 422/400 とするか・エラーコード名は §9 未確定**。

## 6. UI / 画面設計
- 画面 / 状態:
    - チーム詳細配下の「メンバー」タブ/ページ（`pages/teams/...`、`team-core` の詳細画面に連なる想定）。
    - 一覧テーブル（アバター・表示名・ロールバッジ・参加日）。ローディング/空/エラー状態。
- 主要操作:
    - ロール選択 UI（`owner`/`admin`/`member`）。Owner/Admin にのみ操作可、他は読み取り専用。
    - 除名ボタン（確認モーダル）。自分の行には「脱退」ボタン。
    - 最後の owner の降格・脱退・除名はボタン無効化＋理由表示（サーバ側 409 と二重防御）。
- 入力バリデーション・エラー表示:
    - `role` は enum 3 値のみ（クライアントは選択式で担保、最終判定はサーバ）。
    - `ApiError` を捕捉してトースト等で表示（typescript.md）。握り潰さない。
- API 通信は `api/client.ts` の `client` 経由、機能別 `api/teamMembers.ts`（仮）に集約。型は `api/types.ts`（typescript.md）。

## 7. 技術選定と根拠
- 採用:
    - 既存レイヤード縦切り（`Team` リファレンス実装の踏襲）: Input/Output/UseCase/Filter/Result/Repository interface+impl/Controller/Request/Resource（CLAUDE.md / php.md）。
    - ロールは**バック付き enum** `Team\MemberRole`（§10）。
    - 「最後の owner 禁止」は **UseCase（Application 層）のドメインルール**として実装（owner 件数を Repository 経由で確認 → ドメイン判定）。
- 理由:
    - 認可土台として全機能から参照されるため、依存方向・テスト容易性を最優先（§6 / §11）。
    - enum で区分値を表現し生文字列を散らさない（php.md）。
- 却下した代替案 / 却下理由:
    - 「最後の owner 禁止」を DB トリガ/制約で実装 → ドメインルールがインフラに漏れ、テスト・メッセージ国際化が困難。Application 層で表現する。
    - ロールを `teams` 側のフラグで持つ → 多対多のロール管理に不向き。`team_members.role`（§5.1）に従う。
    - 専用「脱退」エンドポイント新設 → §9 の API 一覧に無い。既存 DELETE での分岐を基本線とし、可否は §9 で確定。

## 8. エッジケース・非機能
- エッジケース:
    - 最後の `owner` の降格（US5）/ 除名・脱退（US6）→ 拒否。
    - 自分自身のロール変更（owner が自分を admin に降格 = 実質「最後の owner 降格」になり得る）→ owner 件数判定で拒否されるケースを網羅。
    - 対象 `user_id` がそのチームの非メンバー → 404。
    - 既に同ロールへの変更（no-op）→ 冪等に成功とするか 400 か（§9 寄り、軽微）。
    - private チームの一覧を非メンバーが参照 → 403（§8 可視性）。
    - 存在しない / 論理削除済み team・member の指定 → 404。
- 認可 / 権限（§8）:
    - ロール変更・除名: Owner / Admin のみ。Member / Guest は 403。
    - 脱退: 本人のみ（DELETE で自分自身を指定）。
    - 一覧参照: public はメンバー範囲の方針に従う／private は非メンバー不可（§8 可視性の精緻化は §9）。
- 失敗時の挙動:
    - 例外はコントローラで `try/catch (Exception $e)` → `Log::error(__('messages.error', ...))` + `report($e)` → `ApiResponse::serverError()`（php.md / §6）。
    - ユーザー向け文言は `lang/en/*.php` 翻訳キー（§6 国際化）。
- 性能・可用性で意識する点:
    - 一覧はページング必須・N+1 回避（`users` を eager load して表示名/アバターを取得。§6 性能）。
    - SQL はバインド/Eloquent のみ（§6 セキュリティ）。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`（PHPUnit 12 / `#[Test]` 属性 / AAA / Mockery / **行カバレッジ 100%**）
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`（型エラーなし・ビルド可・`client` 経由・`ApiError` 捕捉）
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - **「最後の owner 降格/脱退/除名 禁止」分岐の網羅**（§4.4 中核）。owner 件数 = 1 のとき降格・脱退・除名がいずれも拒否され、owner 件数 ≥ 2 のときは許可される両系を検証。
        - UseCase テストは `TeamMembershipRepositoryInterface`（仮）を Mockery で差し替え、owner 件数 / 対象メンバーの存在有無を制御して各分岐へ到達させる。
        - Controller の catch 例外分岐は `$this->app->instance(Interface::class, $throwingMock)` で `andThrow` 到達（php.md）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US2/US3（Owner/Admin によるロール変更・除名の成功）。
        - US4（本人脱退の成功）。
        - US5/US6（最後の owner 禁止 = 409 等を返す）。
        - US7（Member/Guest の 403）= §8 認可マトリクスの担保（§12）。
    - DB / 外部サービス依存で注意する点:
        - Feature テストは **MySQL テスト DB（`learning_portal_testing` / `.env.testing`）** 前提。マイグレーションは MySQL 専用生 SQL（`UUID_TO_BIN`/`ENUM`）で sqlite 不可（php.md）。
        - `(team_id, user_id)` 複合ユニーク・FK 制約の確認は Repository/Feature 層で行う。

## 9. 未確定（brainstorming で詰める）
- [ ] **`owner` への昇格と owner の単一/複数**: ロール変更で他メンバーを `owner` にできるか（owner 複数許容か、単一で「譲渡」のみか）。Admin が owner を任命/降格できるか（§8 は「ロール変更」を Owner/Admin 双方に許可しているが owner 操作の可否は未定義）。
- [ ] **`teams.owner_user_id`（§5.1）と `team_members.role=owner` の整合**: owner 変更時に `teams.owner_user_id` を更新するか、role を真実源とするか。両者の同期責務（team-core との境界）。
- [ ] **除名と脱退の経路**: 同一 `DELETE /teams/{id}/members/{user_id}` で「`user_id`==認証ユーザー → 脱退」「他者 → 除名（要 Owner/Admin）」と分岐する前提でよいか。
- [ ] **「最後の owner 禁止」の HTTP ステータス / エラーコード名**: 409 / 422 / 400 のいずれか。`lang/en` の翻訳キー・`ApiResponse` のどのメソッドにマップするか。
- [ ] **メンバー一覧の可視性精緻化**: public チームの一覧を Guest/非メンバーが見られるか（§8 では「メンバーのロール変更・除名」のみ明示。一覧 GET の閲覧範囲は未定義）。
- [ ] **一覧のフィルタ/ソート仕様**: `role` での絞り込み・並び順（`joined_at`/`role`）の既定。
- [ ] **論理削除の有無**: `team_members` を論理削除（`deleted_at`）対象とするか物理削除か（§5 は「論理削除対象は deleted_at」と条件付き。除名/脱退の扱い）。
- [ ] **同ロールへの no-op 変更**の扱い（冪等成功 or 400）。
- [ ] **Admin が他の Admin / Owner を除名できるか**（§8 はロール非依存で「Owner/Admin 可」だが上位ロールへの操作可否は未定義）。
