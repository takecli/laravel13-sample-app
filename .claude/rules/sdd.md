---
description: 仕様駆動開発(SDD)の進め方 — requests → spec → plan → 実装 の順と置き場
alwaysApply: true
---

# 仕様駆動開発（SDD）の進め方

機能の追加・変更は、原則として次の順で進める。各段階で **Superpowers のスキル**を使う。

```
requests →（brainstorming）→ spec →（writing-plans）→ plan →（executing-plans）→ 実装
```

## 段階・スキル・成果物

| 段階 | 使うスキル | 置き場 | 役割（粒度） |
|---|---|---|---|
| 1. 要求 | （人間がラフに記述） | `docs/superpowers/requests/` | **Why / What**。課題・目的・ざっくり要件。`_template.md` を複製して書く |
| 2. 仕様 | `superpowers:brainstorming` | `docs/superpowers/specs/` | 確定要件・受入基準・制約・設計方針 |
| 3. 計画 | `superpowers:writing-plans` | `docs/superpowers/plans/` | **How**。手順・対象ファイル・検証方法 |
| 4. 実装 | `superpowers:executing-plans` ＋ `test-driven-development` / `requesting-code-review` / `verification-before-completion` | コード | チェックポイントごとにレビューしつつ実装 |

## ルール

- **requests だけ人間がラフに書く**（`docs/superpowers/requests/_template.md` を複製）。
- **spec / plan は Superpowers スキルが生成する**ので、独自テンプレートは作らない。`brainstorming` / `writing-plans` の出力フォーマットに従う。
- requests → spec → plan は **同じ slug** で対応付ける。ファイル名は `YYYY-MM-DD-kebab-title.md`。
- 役割を混ぜない：**spec＝何を/なぜ**、**plan＝どう作るか**。requests は荒く、`brainstorming` で鋭くする。
- 実装は本リポの規約に従う：レイヤード構成（[[php]] / `php.md`）・**TDD・行カバレッジ100%**・Pint。plan にこれらの前提を明記する。
- SDD は**重さを調整**して使う。小さな変更は requests / spec を省いて plan だけでも可。
