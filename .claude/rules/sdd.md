---
description: 仕様駆動開発(SDD)の進め方 — requests → draft → spec → plan → 実装 の順と置き場
alwaysApply: true
---

# 仕様駆動開発（SDD）の進め方

機能の追加・変更は、原則として次の順で進める。各段階で **スキル**を使う。
`requests` と `brainstorming` の間に、要求を尋問で叩いて決定を埋める **draft 段階**（`create-draft`）を挟む。

```
requests →（create-draft）→ drafts →（brainstorming）→ specs →（writing-plans）→ plans →（executing-plans）→ 実装
```

## 段階・スキル・成果物

| 段階 | 使うスキル | 置き場 | 役割（粒度） |
|---|---|---|---|
| 1. 要求 | （人間がラフに記述） | `docs/superpowers/requests/` | **Why / What**。課題・目的・ざっくり要件。`_template.md` を複製して書く |
| 2. ドラフト | `create-draft`（内蔵 grilling） | `docs/grill-me/drafts/` | ラフ要求を **1 問ずつの尋問**で叩き、曖昧な設計判断を潰した **brainstorming 入力**。確定 spec ではない |
| 3. 仕様 | `superpowers:brainstorming` | `docs/superpowers/specs/` | 確定要件・受入基準・制約・設計方針 |
| 4. 計画 | `superpowers:writing-plans` | `docs/superpowers/plans/` | **How**。手順・対象ファイル・検証方法 |
| 5. 実装 | `superpowers:executing-plans` ＋ `test-driven-development` / `requesting-code-review` / `verification-before-completion` | コード | チェックポイントごとにレビューしつつ実装 |

## ルール

- **requests だけ人間がラフに書く**（`docs/superpowers/requests/_template.md` を複製）。
- **draft は `create-draft` が生成する**。ラフ要求を内蔵 grilling（**1 問ずつ・推奨回答を添える・コードベースで分かることは先に調べる・設計ツリーを深さ優先**）で叩き、`docs/grill-me/drafts/` に固定フォーマットで保存する。
  - **1 機能 = 1 ドラフト = 1 ファイル**。複数機能が混ざっていたら、まず機能ごとに分割してから各機能に適用する。
  - draft は **確定 spec ではない**。`docs/superpowers/specs/` に直接書かない。確定は `brainstorming` の責務。
- **spec / plan は Superpowers スキルが生成する**ので、独自テンプレートは作らない。`brainstorming` / `writing-plans` の出力フォーマットに従う（**固定フォーマットを持つのは draft（`create-draft`）だけ**）。
- requests → draft → spec → plan は **同じ slug** で対応付ける。ファイル名は `YYYY-MM-DD-kebab-title.md`。
- 役割を混ぜない：**draft＝決定を尋問で埋めた入力**、**spec＝何を/なぜ**、**plan＝どう作るか**。requests は荒く、`create-draft` で決定を引き出し、`brainstorming` で鋭くする。
- 実装は本リポの規約に従う：レイヤード構成（[[php]] / `php.md`）・**TDD・行カバレッジ100%**・Pint。plan にこれらの前提を明記する。
- SDD は**重さを調整**して使う。曖昧さが少ない小さな変更は requests / draft / spec を省いて plan だけでも可。逆に設計判断が多い機能ほど draft（grilling）を厚めに使う。
