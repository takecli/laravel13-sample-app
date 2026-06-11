import type { ApiEnvelope } from "./types"

/**
 * 共通 HTTP クライアント（構成A: セッション/BFF 前提）。
 * - baseURL: /api/v1
 * - セッション Cookie を送信（credentials: include）
 * - 書き込み系は CSRF トークン（meta[name=csrf-token]）を付与
 * - ApiResponse のエンベロープ（data/message/result）を展開して data を返す
 */

const BASE_URL = "/api/v1"

/** API 呼び出しの失敗を表す例外。 */
export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly data?: unknown,
    ) {
        super(message)
        this.name = "ApiError"
    }
}

function getCsrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ""
    )
}

interface RequestOptions {
    method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE"
    body?: unknown
    headers?: Record<string, string>
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    const { method = "GET", body, headers = {} } = options
    const hasBody = body !== undefined
    const isWrite = method !== "GET"

    const res = await fetch(`${BASE_URL}${path}`, {
        method,
        credentials: "include",
        headers: {
            Accept: "application/json",
            ...(hasBody ? { "Content-Type": "application/json" } : {}),
            ...(isWrite ? { "X-CSRF-TOKEN": getCsrfToken() } : {}),
            ...headers,
        },
        body: hasBody ? JSON.stringify(body) : undefined,
    })

    let envelope: ApiEnvelope<T> | undefined
    try {
        envelope = (await res.json()) as ApiEnvelope<T>
    } catch {
        envelope = undefined
    }

    if (!res.ok || envelope?.result === false) {
        const rawMessage = envelope?.message ?? res.statusText
        const message = Array.isArray(rawMessage) ? rawMessage.join("\n") : rawMessage
        throw new ApiError(res.status, message, envelope?.data)
    }

    return envelope!.data
}

export const client = {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, body?: unknown) => request<T>(path, { method: "POST", body }),
    put: <T>(path: string, body?: unknown) => request<T>(path, { method: "PUT", body }),
    patch: <T>(path: string, body?: unknown) => request<T>(path, { method: "PATCH", body }),
    delete: <T>(path: string) => request<T>(path, { method: "DELETE" }),
}
