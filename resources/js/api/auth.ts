import { client } from "./client"
import type { User } from "./types"

export const AuthAPI = {
    /**
     * 現在の認証ユーザーを取得する（GET /api/v1/auth）。
     * 未認証時はサーバが data: null を返すため null になる。
     */
    getCurrentUser(): Promise<User | null> {
        return client.get<User | null>("/auth")
    },
}
