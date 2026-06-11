import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_authenticated/user/edit-user')({
  component: RouteComponent,
})

function RouteComponent() {
  return <div>Hello "/_authenticated/user/edit-user"!</div>
}
