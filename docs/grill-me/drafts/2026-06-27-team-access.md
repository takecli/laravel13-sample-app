# ドラフト: 参加申請 / 招待（team-access）

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。
> 本ドラフトは `docs/requirements.md`（正本）の確定事項を転記し、未確定のみ §9 に残した。

## 0. メタ
- 機能名: 参加申請 / 招待（team-access）
- 作成日: 2026-06-27
- grilling 実行: 一部（正本 requirements.md からの転記。未確定は §9）
- 関連機能（依存・関連する他ドラフト）:
    - 依存: `team-membership`（承認/承諾で `team_members` へ追加。最後の owner 保護・重複防止などメンバー操作はこちらに準拠）
    - 関連: `team-core`（`teams.visibility` が public/private の分岐元）、`notifications`（P3。`join_request_resolved` / `invitation_received` を後段で横断付与）、`auth`（申請/承諾する認証ユーザー）

## 1. 目的（Why）
public チームへの「参加申請 → 承認/却下」と、private チームへの「メール招待 → 承諾」の参加導線を提供し、チームのメンバー構成を安全（重複・期限切れを排除）に拡張できるようにする（要件 §4.5 / US-T1 / US-T2）。

## 2. スコープ
- やること:
    - 参加申請（public チーム）: 申請作成・申請一覧・承認/却下（§7.2）。承認時 `team_members` に `member` で追加。
    - 招待（private チーム）: Admin/Owner がメール宛に招待発行（token・有効期限）・招待取消・被招待者の承諾（§7.3）。承諾時 `team_members` に `member` で追加。
    - 申請/招待の重複防止、期限切れ（expired）の扱い。
    - 非同期メール送信（招待メール）は Laravel Queue に載せる（§6 可用性/非同期）。
- やらないこと（今回対象外）:
    - チームのメンバー一覧・ロール変更・除名・脱退（`team-membership` の責務）。
    - チーム本体の作成・visibility 設定（`team-core` の責務）。
    - アプリ内通知の永続化・既読管理（`notifications` P3。本機能はイベント発火点のみ意識）。
- 非ゴール（誤解されやすいので明示）:
    - public チームに「招待」、private チームに「参加申請」を生やすこと。public=申請、private=招待で固定（§4.5）。
    - 承諾/承認で `member` 以外のロール（admin/owner）を付与すること。付与は `member` 固定（昇格は `team-membership`）。

## 3. ユースケース / ユーザーストーリー
- [ ] US1（US-T1）: Guest（未所属の認証ユーザー）は **public チーム**に参加申請を出し、Admin/Owner が承認/却下する。承認されると `team_members` に追加される。
- [ ] US2（US-T2）: Team Admin/Owner は **private チーム**へメールで招待を発行し、被招待者が token 付き URL から承諾して参加する。
- [ ] US3: Admin/Owner はチームの参加申請一覧（pending 等）を確認して順次さばける。
- [ ] US4: Admin/Owner は未承諾の招待を取消（revoke）できる。
- [ ] US5: 期限切れの招待は承諾できず、expired として扱われる。

## 4. データモデル設計
（正本 §5.1 の `team_join_requests` / `team_invitations` 行、§10 enum を転記）

- エンティティ:
    - `team_join_requests`（参加申請）
    - `team_invitations`（招待）
- 属性 / 型 / 制約:
    - 共通: `id` `CHAR(36)` UUID、`created_at/updated_at` ＋ 作成/更新/削除ユーザーID の監査列、論理削除対象は `deleted_at`（正本 §5 冒頭の全テーブル共通方針）。
    - `team_join_requests`: `team_id`, `user_id`, `message`, `status`（`JoinRequestStatus`: pending/approved/rejected）。
    - `team_invitations`: `team_id`, `invitee_email`, `token`(uniq), `status`（`InvitationStatus`: pending/accepted/revoked/expired）, `expires_at`。
    - enum（§10 バック付き）:
        - `Team\JoinRequestStatus`: `pending` / `approved` / `rejected`
        - `Team\InvitationStatus`: `pending` / `accepted` / `revoked` / `expired`
    - 重複防止: 同一 `team_id`×`user_id` で pending の申請を多重作成させない／同一 `team_id`×`invitee_email` で pending の招待を多重作成させない（具体のユニーク制約方式は §9）。
- リレーション（§5.2）:
    - `teams 1─* team_join_requests`、`teams 1─* team_invitations`。
    - 承認/承諾の結果は `team_members`（team×user uniq, role）へ書き込む。

## 5. API 設計
（正本 §9 の参加申請/招待行。エンベロープは `ApiResponse`、prefix `/api/v1`、auth 必須）

| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
| POST | `/teams/{id}/join-requests` | public チームへ参加申請 | `message?`（任意の申請メッセージ） | 作成された申請（status=pending） | 401 未認証 / 403 private チーム or 既メンバー / 409 重複申請 / 422 検証 / 404 チーム不在 |
| GET | `/teams/{id}/join-requests` | 申請一覧（Admin/Owner） | `status?`・ページング（既定 `Pagination`） | 申請一覧＋ページング | 401 / 403 権限なし / 404 |
| PUT | `/teams/{id}/join-requests/{req_id}` | 承認/却下（Admin/Owner） | `action`(approve/reject) もしくは `status` | 更新後の申請（approved→members 追加） | 401 / 403 / 404 / 409 既処理(pending以外) / 422 |
| POST | `/teams/{id}/invitations` | 招待発行（Admin/Owner, private） | `invitee_email` | 作成された招待（status=pending, expires_at） | 401 / 403 権限/visibility / 409 重複招待 or 既メンバー / 422 |
| POST | `/invitations/{token}/accept` | 招待承諾（被招待者） | （token はパス） | 承諾結果（members 追加） | 401 / 403 メール不一致 / 404 token 不正 / 409 既承諾/取消済 / 410 期限切れ |
| DELETE | `/teams/{id}/invitations/{inv_id}` | 招待取消（Admin/Owner） | — | revoke 結果 | 401 / 403 / 404 / 409 pending 以外 |

- エラーは `ApiResponse`（`badRequest`/`unauthenticate`/`forbidden`/`notFound`/`serverError`）に対応付ける。上表 HTTP コードと `ApiResponse` メソッドの正確な対応は §9 で詰める。

## 6. UI / 画面設計
（本機能はバックエンド API 中心。フロント実装の有無/範囲は brainstorming で確定。下記は想定レベル）
- 画面 / 状態:
    - チーム詳細（public）: 「参加申請」ボタン → 申請中バッジ。
    - チーム管理（Admin/Owner）: 参加申請一覧（pending/approved/rejected フィルタ）、承認/却下操作。招待発行フォーム、招待一覧（pending/expired/…）と取消。
    - 招待承諾ページ: token 付き URL を開き「承諾」操作。期限切れ/取消済/不正 token のエラー表示。
- 主要操作: 申請、承認、却下、招待発行、招待取消、招待承諾。
- 入力バリデーション・エラー表示:
    - `invitee_email`: メール形式必須。
    - `message`: 任意（最大長は §9）。
    - 承諾時の期限切れ・token 不正・メール不一致を明確に表示。

## 7. 技術選定と根拠
- 採用:
    - public=参加申請 / private=招待の二経路を別エンティティ（`team_join_requests` / `team_invitations`）で表現。
    - 招待は不可推測な `token`(uniq) ＋ `expires_at` による期限付きリンク方式。
    - 招待メールは Laravel Queue 経由の非同期送信（§6）。
    - 状態は enum（`JoinRequestStatus` / `InvitationStatus`）で表現し、遷移は §7.2/§7.3 に限定。
- 理由:
    - 正本（§4.5 / §5 / §7 / §10）の確定設計に一致。visibility による導線分離・重複/期限管理という要件を素直に満たす。
    - 非同期化で外部メール障害がリクエスト応答をブロックしない（可用性）。
- 却下した代替案 / 却下理由:
    - 申請と招待を 1 テーブルに統合 → status/属性が分岐し制約が複雑化。正本が 2 テーブルに分離済みのため不採用。
    - 招待を token なしの「メールアドレス指名のみ」で承諾 → なりすまし・URL 直リンク導線が作れない。token 方式を採用。
    - 同期メール送信 → 外部依存でレイテンシ/失敗が応答に直結。Queue 採用（§6）。

## 8. エッジケース・非機能
- エッジケース:
    - 既メンバーが参加申請/被招待 → 拒否（重複参加防止、`team_members` の team×user uniq）。
    - 同一 team×user の pending 申請 / 同一 team×email の pending 招待の二重作成 → 重複防止。
    - pending 以外（approved/rejected/accepted/revoked/expired）への再操作 → 409 等で拒否（§7.2/§7.3 の遷移外）。
    - 期限切れ招待の承諾 → expired として承諾不可。
    - private チームへの参加申請 / public チームへの招待 → visibility 不一致で拒否。
    - token 不正・存在しない → 404。被招待メールと認証ユーザーのメール不一致 → 拒否（要否は §9）。
- 認可 / 権限（§8 マトリクス）:
    - 参加申請の承認/却下・招待発行・招待取消: Owner / Admin のみ。
    - public チームへの参加申請: Guest（未所属の認証ユーザー）。
    - 招待承諾: 被招待者本人（token 保持者）。
    - `api/*` は Keycloak セッション必須。
- 失敗時の挙動: 例外は `Log::error` ＋ `report()` の後 `ApiResponse::serverError()`（§6 可観測性 / コントローラ定型）。メール送信失敗は Queue のリトライに委ねる（§6）。
- 性能・可用性で意識する点: 一覧 API はページング必須・N+1 回避（Infra で eager load）。メール送信は非同期。`token` はユニーク索引、`invitee_email`/`status` の検索を考慮。

## 8.5 テスト方針（準拠先 + 機能固有の観点）
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`（フロント実装を行う場合）
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
        - 招待メール送信（Mail/Queue）はフェイク/モックで検証（実送信しない）。送信失敗・リトライ経路の到達。
        - リポジトリ実装は `Infra/Persistence`。UseCase は Repository interface を Mockery で差し替え、例外分岐（`andThrow`）を到達させる。Controller catch は `$this->app->instance(...)` ＋ throwing mock。
        - 期限切れ判定（現在時刻依存）は時刻を固定して検証（境界: expires_at ちょうど/直後）。
    - 重点的に検証したいユースケース（§3 と対応）:
        - US1: 申請 → 承認 で `team_members` に member 追加 / 却下で追加されない。
        - US2: 招待発行 → token 承諾で member 追加。
        - US4: pending 招待の revoke、US5: expired 承諾不可。
        - 重複防止（重複申請/重複招待/既メンバー）と遷移外操作の拒否（§7.2/§7.3 の状態×権限を網羅）。
        - 認可マトリクス（§8）: Owner/Admin のみ承認・招待発行・取消、Guest のみ public 申請。
    - DB / 外部サービス依存で注意する点:
        - Feature テストは MySQL テスト DB 前提（マイグレーションは MySQL 専用の生 SQL。sqlite 不可）。
        - token のユニーク制約・team×user / team×email の重複制約を DB レベルでも検証。

## 9. 未確定（brainstorming で詰める）
- [ ] 招待 token の有効期限の具体値（例: 7 日 / 72 時間）と生成方式（長さ・乱数源）。
- [ ] 期限切れ（expired）判定の実行方式: (a) スケジューラ/バッチで一括更新、(b) 承諾/参照時の遅延判定（lazy）、(c) 両方。
- [ ] 招待メールの本文・件名・受諾 URL の構成（フロント承諾ページの URL 形式含む）と翻訳キー（`lang/en/*.php`）。
- [ ] 承諾時に「被招待メール＝認証ユーザーのメール」一致を必須にするか（別アカウントでの承諾可否）。未認証で URL を開いた場合のログイン誘導フロー。
- [ ] 重複防止の実装手段: 部分ユニーク索引（pending のみ）/ アプリ側チェック / 論理削除との整合（再申請・再招待を許す条件）。
- [ ] rejected/revoked/expired 後の再申請・再招待の可否とクールダウン有無。
- [ ] PUT `/join-requests/{req_id}` の入力契約（`action: approve|reject` か `status` 直接更新か）。
- [ ] `message` の最大文字数・必須/任意（暫定: 任意）。
- [ ] 承認/承諾/結果を `notifications`（`join_request_resolved` / `invitation_received`）へ流すタイミング（本機能で発火点を用意するか、P3 実装時に後付けか）。
- [ ] 上表の HTTP ステータスと `ApiResponse` メソッド（badRequest/forbidden/notFound 等）の正確な対応付け（410 を使うか 409/404 に寄せるか）。
