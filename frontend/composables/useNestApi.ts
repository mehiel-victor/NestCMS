import type { AbandonedCart, AuthUser, Dashboard, Order, Product, SessionPayload } from '~/types'

interface ApiList<T> {
  data: T
}

export function useNestApi() {
  const config = useRuntimeConfig()
  const apiBase = String(config.public.apiBase).replace(/\/$/, '')
  const session = useAuthSession()
  const demo = useDemoNestApi()

  const usesLocalApiBase = () => {
    const normalized = apiBase.toLowerCase()
    return normalized.includes('localhost') || normalized.includes('127.0.0.1')
  }

  const isDemoMode = () => {
    if (!process.client || !usesLocalApiBase()) {
      return false
    }

    return !['localhost', '127.0.0.1', '::1'].includes(window.location.hostname)
  }

  const parseError = (error: unknown): string => {
    const typed = error as {
      status?: number
      statusCode?: number
      statusText?: string
      response?: { _data?: { error?: string; message?: string } | string }
      message?: string
    }

    if (typed?.response?._data) {
      if (typeof typed.response._data === 'string') {
        return typed.response._data
      }

      if (typeof typed.response._data === 'object') {
        return typed.response._data.error ?? typed.response._data.message ?? 'Falha na chamada.'
      }
    }

    return typed?.message ?? typed?.statusText ?? 'Falha na chamada.'
  }

  const refreshSession = async (): Promise<boolean> => {
    session.hydrate()
    if (!session.refreshToken.value) {
      return false
    }

    try {
      if (isDemoMode()) {
        const response = await demo.refresh(session.refreshToken.value, session.user.value)
        session.setSession(response)
        return true
      }

      const response = await request<SessionPayload>(
        '/api/auth/refresh',
        {
          method: 'POST',
          body: {
            refresh_token: session.refreshToken.value
          }
        },
        false,
        true
      )

      session.setSession(response)
      return true
    } catch {
      session.clearSession()
      return false
    }
  }

  const request = async <T>(
    path: string,
    options: Record<string, unknown> = {},
    requiresAuth = true,
    allowRetry = true
  ) => {
    session.hydrate()

    const headers = {
      ...(options.headers || {}),
      ...(requiresAuth && session.accessToken.value ? { Authorization: `Bearer ${session.accessToken.value}` } : {})
    }

    try {
      return await $fetch<T>(`${apiBase}${path}`, {
        ...options,
        headers
      })
    } catch (error) {
      const status = Number((error as { response?: { status?: number } }).response?.status ?? (error as { status?: number }).status)

      if (requiresAuth && allowRetry && status === 401 && session.refreshToken.value) {
        const didRefresh = await refreshSession()
        if (didRefresh) {
          return request<T>(path, options, true, false)
        }
      }

      throw new Error(parseError(error))
    }
  }

  const setSession = (payload: SessionPayload) => {
    session.setSession(payload)
  }

  const clearSession = () => {
    session.clearSession()
  }

  const authMe = async (): Promise<{ user: AuthUser } & Record<string, unknown>> => {
    return request<{ user: AuthUser } & Record<string, unknown>>('/api/auth/me')
  }

  return {
    health: () => isDemoMode() ? Promise.resolve({ status: 'ok', service: 'nestcms-demo' }) : request<{ status: string; service: string }>('/health'),
    login: async (email: string, password: string) => {
      if (isDemoMode()) {
        const response = await demo.login(email, password)
        setSession(response)
        return response
      }

      const response = await request<SessionPayload>('/api/auth/login', {
        method: 'POST',
        body: { email, password }
      }, false)
      setSession(response)
      return response
    },
    requestMagicLink: async (email: string) => isDemoMode()
      ? demo.requestMagicLink()
      : request<{ message: string }>('/api/auth/magic/request', {
          method: 'POST',
          body: { email }
        }, false),
    consumeMagicLink: async (token: string) => {
      if (isDemoMode()) {
        const response = await demo.consumeMagicLink()
        setSession(response)
        return response
      }

      const response = await request<SessionPayload>(`/api/auth/magic/callback?token=${encodeURIComponent(token)}`, {}, false)
      setSession(response)
      return response
    },
    refresh: async () => {
      const refreshToken = session.refreshToken.value
      if (!refreshToken) {
        throw new Error('Sessão sem token de refresh.')
      }

      if (isDemoMode()) {
        const response = await demo.refresh(refreshToken, session.user.value)
        setSession(response)
        return response
      }

      const response = await request<SessionPayload>(
        '/api/auth/refresh',
        {
          method: 'POST',
          body: { refresh_token: refreshToken }
        },
        false
      )
      setSession(response)
      return response
    },
    me: async () => {
      if (isDemoMode()) {
        const response = await demo.me(session.accessToken.value, session.user.value)
        session.setUser(response.user)
        return response
      }

      const response = await authMe()
      if (response?.user) {
        session.setUser(response.user)
      }

      return response
    },
    logout: async () => {
      if (isDemoMode()) {
        const response = await demo.logout()
        clearSession()
        return response
      }

      const response = await request<{ status: boolean }>('/api/auth/logout', {
        method: 'POST',
        body: { refresh_token: session.refreshToken.value ?? '' }
      }, false).catch(() => null)

      clearSession()
      return response ?? { status: false }
    },
    dashboard: () => isDemoMode() ? demo.dashboard() : request<Dashboard>('/api/dashboard'),
    products: async () => isDemoMode() ? demo.products() : (await request<ApiList<Product[]>>('/api/products')).data,
    publicProducts: async () => isDemoMode() ? demo.publicProducts() : (await request<ApiList<Product[]>>('/api/products?public=1', {}, false)).data,
    createProduct: async (payload: Record<string, unknown>) =>
      isDemoMode() ? demo.createProduct(payload) : (await request<ApiList<Product>>('/api/products', { method: 'POST', body: payload })).data,
    orders: async () => isDemoMode() ? demo.orders() : (await request<ApiList<Order[]>>('/api/orders')).data,
    updateOrderStatus: async (orderId: number, status: string) => isDemoMode()
      ? demo.updateOrderStatus(orderId, status)
      : (await request<ApiList<Order>>(`/api/orders/${orderId}/status`, { method: 'PATCH', body: { status } })).data,
    createPaymentRefund: async (orderId: number, payload: Record<string, unknown>) =>
      isDemoMode() ? demo.createPaymentRefund(orderId) : (await request<ApiList<Record<string, unknown>>>(`/api/orders/${orderId}/refunds`, { method: 'POST', body: payload })).data,
    submitPaymentReview: async (orderId: number, payload: Record<string, unknown>) => isDemoMode()
      ? demo.submitPaymentReview(orderId, payload)
      : (await request<ApiList<Record<string, unknown>>>(`/api/orders/${orderId}/payment-review`, { method: 'POST', body: payload })).data,
    pendingPaymentReport: async (minutes: number = 60) => isDemoMode()
      ? demo.pendingPaymentReport(minutes)
      : (await request<ApiList<Record<string, unknown>>>(`/api/payments/pending-report?minutes=${minutes}`)).data,
    abandonedCarts: async () => isDemoMode() ? demo.abandonedCarts() : (await request<ApiList<AbandonedCart[]>>('/api/marketing/abandoned-carts')).data,
    sendRecovery: async (cartId: number) => isDemoMode()
      ? demo.sendRecovery(cartId)
      : request<ApiList<Record<string, unknown>>>(`/api/marketing/abandoned-carts/${cartId}/send`, { method: 'POST' }),
    checkout: async (payload: Record<string, unknown>) =>
      isDemoMode() ? demo.checkout(payload) : (await request<ApiList<Record<string, unknown>>>('/api/checkout', { method: 'POST', body: payload })).data,
    revenue: async () => isDemoMode() ? demo.revenue() : (await request<ApiList<Record<string, unknown>>>('/api/analytics/revenue')).data
  }
}
