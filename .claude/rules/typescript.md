---
description: TypeScript / React コーディング規約（SPA: TanStack Router + Chakra v3）
globs:
  - "**/*.ts"
  - "**/*.tsx"
alwaysApply: false
---

# TypeScript / React コーディング規約

対象: `*.ts` `*.tsx`（`resources/js/` 配下）。共通規約は `general.md` を前提とする。

## 言語・スタイル

- **TypeScript `6`（`strict: true`）/ React `19` / Vite `8`。** リンタ／フォーマッタは未導入のため**周囲のファイルに厳密に倣う**。
- 既定スタイル（`api/` 層・`types.ts`・`routes/` がリファレンス）:
  - **ダブルクォート**、**インデント 4 スペース**、**セミコロンなし**。
  - **名前付き export**（`export const` / `export interface`）。`default export` は使わない。
  - import のパスエイリアスは **`@/`**（= `resources/js/`）。相対の `../../` を避ける。
- `any` 禁止。不明な値は `unknown` で受けて絞り込む。
- 既存ファイルを編集するときは、そのファイルのスタイル（セミコロン有無など）に合わせる。

## 型

- API 入出力（DTO）型は **`api/types.ts` に集約**する。サーバ JSON の形だけを宣言する。
- 日付・日時は **ISO 8601 文字列**（`created_at: string`）として扱う。
- 共通エンベロープは `ApiEnvelope<T>`（`{ data, message, result }`）。

## API 通信

- HTTP は必ず **`api/client.ts` の `client`** を経由する（`fetch` を直書きしない）。
  - `baseURL = /api/v1`、`credentials: "include"`（セッション Cookie）、書き込み系は `meta[name=csrf-token]` を自動付与。
  - エンベロープを展開して `data` を返し、失敗時は **`ApiError`** を投げる。
- 機能ごとに **`api/<feature>.ts`** を追加し、エンドポイントをまとめる（例: `AuthAPI.getCurrentUser()`）。
- 呼び出し側は `ApiError` を捕捉してユーザー向けに処理する。エラーを握り潰さない。

```ts
// api/team.ts
import { client } from "./client"
import type { TeamList } from "./types"

export const TeamAPI = {
    list(): Promise<TeamList> {
        return client.get<TeamList>("/teams")
    },
}
```

## コンポーネント

- **関数コンポーネント**のみ。props は `interface` で明示する。
- UI は **Chakra UI v3** と自前ラッパ（`components/ui/`）を使う。素の Chakra を直接使うより、既存ラッパ（`Button` 等）を優先する。
- ラッパで `ref` を渡す必要がある場合は `React.forwardRef` を使う（`components/ui/button.tsx` がパターン）。
- 既定のカラーパレットは `"brand"`、テーマは `theme.ts`、カラーモードは `next-themes`（`providers/`）。
- レイアウトは `components/layout/`、ページは `pages/<feature>/` に置く。

## ルーティング（TanStack Router）

- **ファイルベースルーティング。** ルートは `routes/` に置き、`createFileRoute(...)` で定義する。
- **`routeTree.gen.ts` は自動生成。手で編集しない**（`@tanstack/router-plugin` が再生成する）。
- 認証が必要な画面は `routes/_authenticated/` 配下に置く。

## やってはいけない

- `fetch` をコンポーネントから直書きする（`client` を使う）。
- `routeTree.gen.ts` を手編集する。
- `any` / `default export` / `console.log` の残置。
- DTO 型をコンポーネント内にローカル定義して散らす（`api/types.ts` に集約）。
