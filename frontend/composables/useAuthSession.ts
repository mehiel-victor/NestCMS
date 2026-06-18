import { computed } from 'vue'
import type { AuthUser, SessionPayload } from '~/types'

const ACCESS_TOKEN_KEY = 'nestcms_auth_access_token'
const REFRESH_TOKEN_KEY = 'nestcms_auth_refresh_token'
const USER_KEY = 'nestcms_auth_user'

export function useAuthSession() {
  const accessToken = useState<string | null>('auth.accessToken', () => null)
  const refreshToken = useState<string | null>('auth.refreshToken', () => null)
  const user = useState<AuthUser | null>('auth.user', () => null)
  const loaded = useState<boolean>('auth.loaded', () => false)

  const hydrate = () => {
    if (!process.client || loaded.value) {
      return
    }

    loaded.value = true

    accessToken.value = localStorage.getItem(ACCESS_TOKEN_KEY)
    refreshToken.value = localStorage.getItem(REFRESH_TOKEN_KEY)

    const rawUser = localStorage.getItem(USER_KEY)
    if (rawUser) {
      try {
        user.value = JSON.parse(rawUser) as AuthUser
      } catch {
        user.value = null
      }
    }
  }

  const persist = () => {
    if (!process.client) {
      return
    }

    if (accessToken.value) {
      localStorage.setItem(ACCESS_TOKEN_KEY, accessToken.value)
    } else {
      localStorage.removeItem(ACCESS_TOKEN_KEY)
    }

    if (refreshToken.value) {
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken.value)
    } else {
      localStorage.removeItem(REFRESH_TOKEN_KEY)
    }

    if (user.value) {
      localStorage.setItem(USER_KEY, JSON.stringify(user.value))
    } else {
      localStorage.removeItem(USER_KEY)
    }
  }

  const setSession = (payload: SessionPayload) => {
    accessToken.value = payload.access_token
    refreshToken.value = payload.refresh_token
    setUser(payload.user)
  }

  const setUser = (nextUser: AuthUser | null) => {
    user.value = nextUser
    persist()
  }

  const clearSession = () => {
    accessToken.value = null
    refreshToken.value = null
    user.value = null
    persist()
  }

  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)

  const hasRole = (roles: string[]) => !!user.value && roles.includes(user.value.role)

  return {
    accessToken,
    refreshToken,
    user,
    hydrate,
    setSession,
    setUser,
    clearSession,
    isAuthenticated,
    hasRole
  }
}
