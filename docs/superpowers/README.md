# 仕様駆動開発（SDD）ドキュメント

機能開発の各段階の成果物を置く。進め方の規約は `.claude/rules/sdd.md` を参照。

```
requests →（brainstorming）→ specs →（writing-plans）→ plans →（executing-plans）→ 実装
```

| ディレクトリ | 段階 | 生成方法 |
|---|---|---|
| `requests/` | 要求（Why/What をラフに） | **人間が記述**。`requests/_template.md` を複製 |
| `specs/` | 仕様（確定要件・受入基準・設計方針） | `superpowers:brainstorming` が生成 |
| `plans/` | 計画（How・手順・検証方法） | `superpowers:writing-plans` が生成 |

## 規約

- ファイル名は `YYYY-MM-DD-kebab-title.md`。requests → spec → plan は **同じ slug** で対応付ける。
- **テンプレートは requests のみ**。specs / plans は Superpowers スキルの出力フォーマットに従う（独自テンプレを作らない）。
- 実装はレイヤード構成・TDD・カバレッジ100%（`.claude/rules/php.md`）に従う。
