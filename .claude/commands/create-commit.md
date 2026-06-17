---
description: ステージ済み（git add 済み）の変更だけをコミットする
argument-hint: "[コミットメッセージ/意図(任意)]"
allowed-tools: Bash(git status:*), Bash(git diff:*), Bash(git commit:*), Bash(git log:*), Bash(git branch:*), Bash(git switch:*)
---

ステージング済み（`git add` 済み）の変更**だけ**をコミットしてください。

## 現在の状態（自動取得）

- ブランチ: !`git branch --show-current`
- ステージ済み: !`git diff --cached --name-status`
- ステージ済みの差分量: !`git diff --cached --stat | tail -1`
- 未ステージ/未追跡: !`git status --short | grep -vE '^[MADRC] ' || echo "(なし)"`

## 厳守事項

- **ステージ済みの変更のみ**をコミットする。`git add` / `git add -A` / `git commit -a` で勝手にステージしない。
- ステージ済みが無ければ**コミットせず**、その旨を伝えて終了する。
- 未ステージ/未追跡がある場合は「コミットされない」ことを明示する。
- **push はしない。**
- **作業ブランチが `feature/**` 以外の場合は、コミット前に必ずユーザーへ次の3択を確認する**（`main` / `master` / `develop` / `hotfix/**` などの保護・共有ブランチへの直コミットを防ぐ）:
  1. **新しいブランチを切る** — `feature/<名前>` を作成して切り替えてからコミットする（ブランチ名はユーザーに尋ねる。ステージ済みの変更は切り替え後も維持される）
  2. **そのままコミット** — 現ブランチのままコミットする
  3. **中止** — コミットしない
  `feature/**` のときは確認不要でそのまま進めてよい。現ブランチは上記「現在の状態」で取得済み。

## コミットメッセージ規約（Conventional Commits）

1行目: `<type>(<scope>): <要約>`

- **type（必須・英語の固定語彙）**:
  - `feat` 機能追加 / `fix` バグ修正 / `docs` ドキュメント / `test` テスト追加・修正
  - `refactor` 挙動を変えない改善 / `perf` 性能改善 / `style` 整形のみ（挙動なし）
  - `build` ビルド・Docker・依存 / `ci` CI設定 / `chore` 雑務・設定・タスク / `revert` 取り消し
- **scope（任意・英語）**: 影響範囲。例 `team` `auth` `domain` `application` `infra` `http` `migration` `seeder` `rules` `taskfile` `docker`
- **要約（必須）**: 日本語・命令形・簡潔（全角50字目安）・**末尾に句点を付けない**。
- 破壊的変更は type 直後に `!` を付ける（例 `feat!:`）か、本文フッターに `BREAKING CHANGE: 説明` を書く。
- 1行目の後は空行を空け、本文に変更点（What と Why）を箇条書きで書く。

例:

```
feat(team): チーム申請テーブルとシーダーを追加
fix(auth): callback の例外時に dd() でプロセス停止する不具合を修正
test: 全層のテストを追加し行カバレッジを100%にする
docs: CLAUDE.md とコーディング規約を整備
chore(taskfile): make / make-table タスクを追加
```

## 手順

1. 上記「現在の状態」を確認する。ステージ済みが空なら中止。
2. **ブランチ確認**: 現ブランチが `feature/**` 以外なら、上記「3択」をユーザーに確認し選択に従う。
   - 新しいブランチ → 名前を尋ね、`git switch -c feature/<名前>` で切り替えてから次へ進む（ステージ内容は維持される）。
   - 中止 → 何もせず終了。
   - `feature/**` の場合はこのステップを飛ばす。
3. `git diff --cached` の内容を読み、コミットメッセージを作成する。
   - 引数 `$ARGUMENTS` があれば、それをメッセージ/意図として最優先で反映する。
   - 形式は上記「コミットメッセージ規約（Conventional Commits）」に**必ず従う**。
   - **What だけでなく Why** を簡潔に書く。
   - 無関係な変更が混在している場合は指摘し、コミット分割を提案する（指示があれば従う）。
4. 末尾に以下のトレーラーを付けてコミットする:

   ```
   Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
   ```

5. `git log -1 --stat --oneline` で結果を表示し、コミットされた/されなかったファイルを報告する。
