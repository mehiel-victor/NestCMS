export default defineNuxtRouteMiddleware(async (to) => {
  if (process.server) {
    return
  }

  const isPublic =
    to.meta?.requiresAuth === false ||
    to.path === '/login' ||
    to.path === '/auth/callback' ||
    to.path.startsWith('/checkout')

  if (isPublic || to.path.startsWith('/api')) {
    return
  }

  const session = useAuthSession()
  const api = useNestApi()
  const allowedRoles = (to.meta?.allowedRoles || []) as string[]

  session.hydrate()

  const nextPath = encodeURIComponent(to.fullPath)

  if (!session.accessToken.value) {
    if (!session.refreshToken.value) {
      return navigateTo(`/login?next=${nextPath}`)
    }

    const refreshed = await api.refresh().catch(() => null)
    if (!refreshed) {
      return navigateTo(`/login?next=${nextPath}`)
    }
  }

  if (session.accessToken.value && !session.user.value) {
    try {
      await api.me()
    } catch {
      session.clearSession()
      return navigateTo(`/login?next=${nextPath}`)
    }
  }

  if (session.user.value && allowedRoles.length > 0 && !allowedRoles.includes(session.user.value.role)) {
    return navigateTo('/login?reason=forbidden')
  }
}
