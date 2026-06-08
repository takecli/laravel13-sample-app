import { createFileRoute } from "@tanstack/react-router"

export const Route = createFileRoute('/_unauthenticated/home')({
    component: Index,
})

function Index() {
    return <h1>Hello React + TypeScript on Laravel1</h1>
}