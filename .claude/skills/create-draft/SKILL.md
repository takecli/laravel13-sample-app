---
name: create-draft
description: "Use this skill when the user wants to turn a rough feature request into a decision-filled draft BEFORE handing it to superpowers brainstorming. Triggers when the user says 'create draft', 'create-draft', 'ドラフトを作る', '要求を叩いて', or asks to pin down ambiguous design decisions for a single feature before writing a spec. This skill runs a built-in grilling interrogation (no external dependency) to interview the user about one feature, then captures the answers into a fixed per-feature draft format saved under docs/grill-me/drafts/. Do NOT use this for finalizing specs (that is superpowers brainstorming) or for implementation planning (that is superpowers writing-plans). This is strictly the pre-processing step that produces input for brainstorming."
---

# create-draft

ラフな機能要求を、**内蔵の grilling（尋問）** で「決定事項が埋まったドラフト」に変換し、
決まったフォーマットで `docs/grill-me/drafts/` に保存するスキル。

このドラフトは **superpowers の brainstorming に渡す入力**であり、確定 spec ではない。
確定 spec への整形は brainstorming が担う。本スキルは前処理（決定の引き出し）に専念する。

> 尋問の規律は Matt Pocock の grill-me / grilling（MIT）から取り込み、本スキルに内製した。
> 外部スキルへの依存はない（このリポジトリ単体で完結する）。

## 位置づけ（データフロー上の役割）

```
ラフ要求
  └→ create-draft（このスキル：内蔵 grilling で尋問）→ docs/grill-me/drafts/<feature>.md
        └→ superpowers:brainstorming（整形・確定）   → docs/superpowers/specs/<feature>.md
              └→ superpowers:writing-plans            → docs/superpowers/plans/...
                    └→ executing-plans / subagent-driven-development（実装）
```

## トリガー

- ユーザーが「ドラフトを作って」「create-draft」「要求を叩いて」と指示したとき
- ラフな機能要求があり、確定 spec を書く前に曖昧な設計判断を潰したいとき

## 前提

- 1 回の実行で扱うのは **1 機能**。複数機能が混ざっていたら、まず機能ごとに分割してから本スキルを各機能に適用する。

## 振る舞い（Process）

### 1. スコープ確認
渡された要求が単一機能か判定する。複数の独立機能を含むなら、ここで機能リストに分解し、
ユーザーに「どの機能からドラフト化するか」を確認する。1 機能 = 1 ドラフト = 1 ファイルを厳守する。

### 2. grilling（内蔵の尋問ループ）
その機能に対して、以下の規律で尋問する。これは外部スキルではなく本スキルの中核手順である。

**尋問の規律（厳守）:**
1. **一度に 1 問だけ。** 決して複数の問いを束ねない。
2. **各質問に推奨回答を必ず添える。** 「どう思いますか？」で済ませない。
   自分の推し案と 1 文の根拠をセットで出す（例:「FR は feature-based を推奨。理由は…」）。
3. **コードベースで分かることは先に調べる。** grep / 既存ファイル参照で解決する問いは、
   聞く前に自分で調べ、「調べた結果こうだった。これで合っているか？」の確認に変える。
4. **設計ツリーを深さ優先で歩く。** ひとつの枝（例: データモデル）を片付けてから次の枝（例: API）へ。
   枝の途中で別の枝に飛ばない。
5. **依存関係を解決しながら進む。** ある決定が後続の決定を縛るなら、その順で問う。

**尋問の重点領域**（後段で defaults 任せになりやすい順）:
- データモデル（エンティティ・属性・型・制約・関連）
- API 設計（エンドポイント・リクエスト/レスポンス・エラー）
- UI / 画面（必要な画面・状態・操作・バリデーション表示）
- 技術選定の根拠（なぜその案か、却下した代替案）
- エッジケース・非機能（性能・認可・失敗時挙動）
- テスト到達性（この機能でカバレッジ目標を満たすうえで、到達が難しい分岐・モックが要る依存はどこか）

> テスト規約そのもの（カバレッジ閾値・PHPUnit/Vitest 構成・命名規則など）は問わない。
> それは `.claude/rules/*.md` に定義済み。ここで問うのは「規約を満たすために、
> この機能固有で意識すべきテスト観点」だけ。規約の中身を尋問・転記しないこと。

**時間制御（勉強会・短時間運用）:**
冒頭で範囲を宣言する。「未決定の項目だけを優先し、1 領域あたり 1〜2 問に絞る」。
全枝を 37 問フルで潰すモードと、要点だけ叩く短縮モードを、開始時にユーザーへ確認してもよい。

各回答の出力フォーマット（1 問ずつ）:
```
Q[i]/[total（暫定）]: <質問>
推奨: <自分の推し案 + 1 文の根拠>
（または: コードベースを調べた結果 <根拠>。これで合っているか確認したい）
```

### 3. 回答をドラフトフォーマットへ転記
尋問で確定した内容を、後述の「ドラフトフォーマット」の各セクションに埋める。
未確定のまま残った点は `## 9. 未確定（brainstorming で詰める）` に正直に積み残す。
曖昧な決定を、さも確定したかのように埋めない。

### 4. 保存
`docs/grill-me/drafts/<YYYY-MM-DD>-<kebab-feature>.md` に保存する。
ディレクトリが無ければ作成する。

### 5. 次工程の案内
保存後、ユーザーに次の一手を伝える:
「このドラフトを superpowers の brainstorming に渡して確定 spec を作りますか？」

## 出力（Output）

`docs/grill-me/drafts/<YYYY-MM-DD>-<kebab-feature>.md` に、以下フォーマットのドラフト 1 機能分。

---

## ドラフトフォーマット（機能単位 / レイヤーは章として内包）

```markdown
# ドラフト: <機能名>

> 内蔵 grilling で叩いた決定事項を集約した **brainstorming 入力用ドラフト**。
> これは確定 spec ではない。確定は superpowers:brainstorming が行う。
> 1 ファイル = 1 機能。API/UI 等のレイヤーはこの機能の下位章として持つ。

## 0. メタ
- 機能名:
- 作成日: <YYYY-MM-DD>
- grilling 実行: 済 / 一部（短縮モード）
- 関連機能（依存・関連する他ドラフト）:

## 1. 目的（Why）
<この機能が解決する課題。1〜2 文。>

## 2. スコープ
- やること:
- やらないこと（今回対象外）:
- 非ゴール（誤解されやすいので明示）:

## 3. ユースケース / ユーザーストーリー
- [ ] US1: <誰が><何を><なぜ>
- [ ] US2:

## 4. データモデル設計
<エンティティ・属性・型・制約・関連。grilling で確定した範囲を書く。>
- エンティティ:
- 属性 / 型 / 制約:
- リレーション:

## 5. API 設計
<この機能が公開/利用するエンドポイント。機能内の章として持つ。>
| メソッド | パス | 概要 | 主なリクエスト | 主なレスポンス | エラー |
|---|---|---|---|---|---|
|  |  |  |  |  |  |

## 6. UI / 画面設計
<必要な画面・状態遷移・操作・バリデーション表示。API を持たない機能なら「該当なし」と明記。>
- 画面 / 状態:
- 主要操作:
- 入力バリデーション・エラー表示:

## 7. 技術選定と根拠
<grilling に「なぜその案か」を問わせた結果。却下した代替案も残す。>
- 採用:
- 理由:
- 却下した代替案 / 却下理由:

## 8. エッジケース・非機能
- エッジケース:
- 認可 / 権限:
- 失敗時の挙動:
- 性能・可用性で意識する点:

## 8.5 テスト方針（準拠先 + 機能固有の観点）
<規約の中身は書かない。準拠する rules ファイルへの参照と、この機能固有の観点だけを書く。>
<1 機能がバックエンド/フロントエンドの両方に跨る場合は、レイヤーごとに準拠先を併記する。>
- 準拠 rules:
    - バックエンド（該当する場合）: `.claude/rules/php.md`
    - フロントエンド（該当する場合）: `.claude/rules/typescript.md`
- 本機能固有のテスト観点:
    - 到達が難しい分岐 / 要モックの依存:
    - 重点的に検証したいユースケース（§3 と対応）:
    - DB / 外部サービス依存で注意する点（例: MySQL 専用 SQL、外部 API のスタブ）:

## 9. 未確定（brainstorming で詰める）
<grilling でも決め切れなかった論点を正直に残す。ここが brainstorming の出発点になる。>
- [ ]
- [ ]
```

## Anti-patterns（やってはいけないこと）

- ❌ レイヤー軸（API設計書 / UI設計書）でファイルを分割する
      → 1 機能が複数ファイルに割れ、brainstorming/writing-plans が機能単位で扱えなくなる。
        API・UI は「機能ファイルの中の章」にする。
- ❌ 未確定の決定を、確定したように埋める
      → grilling の意義が消える。決め切れなければ `## 9. 未確定` に残す。
- ❌ 質問を束ねて一気に投げる
      → 規律違反。必ず 1 問ずつ、推奨回答を添える。
- ❌ このスキルで確定 spec を名乗る / `docs/superpowers/specs/` に直接書く
      → 確定は brainstorming の責務。ここは drafts 止まり。
- ❌ 複数機能を 1 ドラフトに詰め込む
      → 1 機能 = 1 ファイル。混ざっていたらまず分解。
- ❌ テスト規約の中身（カバレッジ閾値・PHPUnit/Vitest 構成・命名規則など）をドラフトに転記する
      → 規約は `.claude/rules/*.md` が単一の正。ドラフトには「参照」と「機能固有の観点」だけ書く。
        中身をコピーすると rules 更新時にドラフトが古いまま取り残される。

## Related Skills

- superpowers:brainstorming — 本スキルの出力（draft）を受け取り、確定 spec に整形する次工程。
- superpowers:writing-plans — 確定 spec を実行計画に落とす後続工程。

## 出典 / ライセンス

尋問の規律（1 問ずつ・推奨回答を添える・コードベース優先・深さ優先で枝を解決）は
Matt Pocock の grill-me / grilling（MIT, https://github.com/mattpocock/skills）から取り込んだ。
本スキルはそれを 1 ファイルに内製した派生物であり、外部スキルへの実行時依存はない。
