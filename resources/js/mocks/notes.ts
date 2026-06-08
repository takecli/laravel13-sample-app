/**
 * 投稿（学習資料）一覧のモックデータ。
 * 「所属しているチームの投稿一覧」を想定し、ログインユーザーが属するチームの
 * 公開済み記事を表す（API 接続前の表示確認用）。
 */

export type NoteStatus = "draft" | "published";

export interface MockNote {
    id: string;
    title: string;
    /** 本文の抜粋（一覧表示用） */
    excerpt: string;
    author: { name: string };
    team: { name: string };
    tags: string[];
    status: NoteStatus;
    /** 公開日（ISO: YYYY-MM-DD） */
    publishedAt: string;
    likeCount: number;
    commentCount: number;
}

export const mockNotes: MockNote[] = [
    {
        id: "0c9a1f00-0000-4000-8000-000000000001",
        title: "TanStack Router 入門：ファイルベースルーティングの基礎",
        excerpt:
            "TanStack Router のファイルベースルーティングで _authenticated / _unauthenticated を使った認証グループの作り方をまとめました。",
        author: { name: "山田 太郎" },
        team: { name: "フロントエンド勉強会" },
        tags: ["React", "TanStack Router", "TypeScript"],
        status: "published",
        publishedAt: "2026-06-05",
        likeCount: 12,
        commentCount: 3,
    },
    {
        id: "0c9a1f00-0000-4000-8000-000000000002",
        title: "Chakra UI v3 のテーマ設計とセマンティックトークン",
        excerpt:
            "defaultConfig をベースに brand パレットと semantic token を定義し、ライト/ダーク両対応のテーマを作る手順を解説します。",
        author: { name: "佐藤 花子" },
        team: { name: "フロントエンド勉強会" },
        tags: ["Chakra UI", "Design System"],
        status: "published",
        publishedAt: "2026-06-03",
        likeCount: 8,
        commentCount: 1,
    },
    {
        id: "0c9a1f00-0000-4000-8000-000000000003",
        title: "Laravel × Vite で React SPA を配信する構成",
        excerpt:
            "Blade の殻を返しつつ Vite 開発サーバの HMR を効かせる、Docker 環境での実践的なセットアップを紹介します。",
        author: { name: "鈴木 一郎" },
        team: { name: "バックエンド勉強会" },
        tags: ["Laravel", "Vite", "Docker"],
        status: "published",
        publishedAt: "2026-05-30",
        likeCount: 20,
        commentCount: 5,
    },
    {
        id: "0c9a1f00-0000-4000-8000-000000000004",
        title: "BINARY(16) UUID を主キーにするスキーマ設計",
        excerpt:
            "UUID_TO_BIN を既定値にしたテーブル設計と、監査列・ソフトデリートの方針について整理しました。",
        author: { name: "高橋 美咲" },
        team: { name: "バックエンド勉強会" },
        tags: ["MySQL", "DB設計"],
        status: "published",
        publishedAt: "2026-05-28",
        likeCount: 15,
        commentCount: 7,
    },
    {
        id: "0c9a1f00-0000-4000-8000-000000000005",
        title: "Keycloak SSO 連携の概要メモ",
        excerpt:
            "users.keycloak_id でトークンの sub と名寄せする方針と、アプリ側でパスワードを持たない設計の勘所。",
        author: { name: "山田 太郎" },
        team: { name: "バックエンド勉強会" },
        tags: ["Keycloak", "認証"],
        status: "published",
        publishedAt: "2026-05-25",
        likeCount: 6,
        commentCount: 0,
    },
];
