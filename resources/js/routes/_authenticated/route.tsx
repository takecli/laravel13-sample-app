import { useAuth } from '@/context/auth-context'
import { Center, Spinner } from '@chakra-ui/react'
import { createFileRoute, Outlet } from '@tanstack/react-router'
import { useEffect } from 'react'

export const Route = createFileRoute('/_authenticated')({
  component: RouteComponent,
})

function RouteComponent() {
  const { isAuthenticated, loading } = useAuth()

    useEffect(() => {
        if (!loading && !isAuthenticated) {
            // Keycloak ログインへ全画面遷移（外部URLなので router の redirect ではなく location を使う）
            window.location.href = '/api/v1/auth/keycloak/redirect' 
        }
    }, [loading, isAuthenticated])

    if (loading) {
        return (
            <Center minH="100dvh">
                <Spinner />
            </Center>
        )
    }

    if (!isAuthenticated) {
        return null // リダイレクト待ち（描画しない）
    }

    return <Outlet />
}
