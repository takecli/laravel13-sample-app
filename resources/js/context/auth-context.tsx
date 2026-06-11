import React, { createContext, useCallback, useContext, useEffect, useState } from "react"
import { AuthAPI } from "@/api/auth"
import type { User } from "@/api/types"

export interface AuthContextValue {
    user: User | null
    isAuthenticated: boolean
    /** 初回の認証情報取得が完了するまで true */
    loading: boolean
    /** 認証情報を再取得する（ログイン後などに呼ぶ） */
    refresh: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | undefined>(undefined)

interface AuthProviderProps {
    children: React.ReactNode
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }: AuthProviderProps) => {
    const [user, setUser] = useState<User | null>(null)
    const [loading, setLoading] = useState(true)

    const refresh = useCallback(async () => {
        setLoading(true)
        try {
            const current = await AuthAPI.getCurrentUser()
            setUser(current)
        } catch {
            // 401 等は未認証として扱う
            setUser(null)
        } finally {
            setLoading(false)
        }
    }, [])

    useEffect(() => {
        void refresh()
    }, [refresh])

    const value: AuthContextValue = {
        user,
        isAuthenticated: user !== null,
        loading,
        refresh,
    }

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

/** 認証コンテキストを取得するフック。AuthProvider の内側で使うこと。 */
export function useAuth(): AuthContextValue {
    const ctx = useContext(AuthContext)
    if (ctx === undefined) {
        throw new Error("useAuth は AuthProvider の内側で使用してください")
    }
    return ctx
}
