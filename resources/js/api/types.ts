/**
 * API の契約（DTO）型。サーバが返す JSON の形だけを宣言する。
 */

/** ApiResponse 共通エンベロープ（data / message / result）。 */
export interface ApiEnvelope<T> {
    data: T
    message: string | string[]
    result: boolean
}

/** users テーブルに対応（email_verified_at は廃止済み）。 */
export interface User {
    id: string
    keycloak_id: string
    name: string
    email: string
    /** ISO 8601 文字列 */
    created_at: string
    /** ISO 8601 文字列 */
    updated_at: string
}
