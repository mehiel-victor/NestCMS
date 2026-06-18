import type { AbandonedCart, AuthUser, Dashboard, Order, Product, SessionPayload } from '~/types'

interface ApiList<T> {
  data: T
}

export function useNestApi() {
  const config = useRuntimeConfig()
  const apiBase = String(config.public.apiBase).replace(/\/$/, '')
  const session = useAuthSession()

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
    health: () => request<{ status: string; service: string }>('/health'),
    login: async (email: string, password: string) => {
      const response = await request<SessionPayload>('/api/auth/login', {
        method: 'POST',
        body: { email, password }
      }, false)
      setSession(response)
      return response
    },
    requestMagicLink: async (email: string) => request<{ message: string }>('/api/auth/magic/request', {
      method: 'POST',
      body: { email }
    }, false),
    consumeMagicLink: async (token: string) => {
      const response = await request<SessionPayload>(`/api/auth/magic/callback?token=${encodeURIComponent(token)}`, {}, false)
      setSession(response)
      return response
    },
    refresh: async () => {
      const refreshToken = session.refreshToken.value
      if (!refreshToken) {
        throw new Error('Sessão sem token de refresh.')
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
      const response = await authMe()
      if (response?.user) {
        session.setUser(response.user)
      }

      return response
    },
    logout: async () => {
      const response = await request<{ status: boolean }>('/api/auth/logout', {
        method: 'POST',
        body: { refresh_token: session.refreshToken.value ?? '' }
      }, false).catch(() => null)

      clearSession()
      return response ?? { status: false }
    },
    dashboard: () => request<Dashboard>('/api/dashboard'),
    products: async () => (await request<ApiList<Product[]>>('/api/products')).data,
    publicProducts: async () => (await request<ApiList<Product[]>>('/api/products?public=1', {}, false)).data,
    createProduct: async (payload: Record<string, unknown>) =>
      (await request<ApiList<Product>>('/api/products', { method: 'POST', body: payload })).data,
    orders: async () => (await request<ApiList<Order[]>>('/api/orders')).data,
    updateOrderStatus: async (orderId: number, status: string) =>
      (await request<ApiList<Order>>(`/api/orders/${orderId}/status`, { method: 'PATCH', body: { status } })).data,
    createPaymentRefund: async (orderId: number, payload: Record<string, unknown>) =>
      (await request<ApiList<Record<string, unknown>>>(`/api/orders/${orderId}/refunds`, { method: 'POST', body: payload })).data,
    submitPaymentReview: async (orderId: number, payload: Record<string, unknown>) =>
      (await request<ApiList<Record<string, unknown>>>(`/api/orders/${orderId}/payment-review`, { method: 'POST', body: payload })).data,
    pendingPaymentReport: async (minutes: number = 60) =>
      (await request<ApiList<Record<string, unknown>>>(`/api/payments/pending-report?minutes=${minutes}`)).data,
    abandonedCarts: async () => (await request<ApiList<AbandonedCart[]>>('/api/marketing/abandoned-carts')).data,
    sendRecovery: async (cartId: number) =>
      request<ApiList<Record<string, unknown>>>(`/api/marketing/abandoned-carts/${cartId}/send`, { method: 'POST' }),
    checkout: async (payload: Record<string, unknown>) =>
      (await request<ApiList<Record<string, unknown>>>('/api/checkout', { method: 'POST', body: payload })).data,
    revenue: async () => (await request<ApiList<Record<string, unknown>>>('/api/analytics/revenue')).data
  }
}
