import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_authenticated/user/edit-user')({
  component: RouteComponent,
})

function RouteComponent() {
  return <div>Hello "/_authenticated/user/edit-user"!</div>
}
