import type { AuthUser, SessionPayload } from '~/types'

export function useNestApi() {
  const session = useAuthSession()
  const demo = useDemoNestApi()

  const setSession = (payload: SessionPayload) => {
    session.setSession(payload)
  }

  const clearSession = () => {
    session.clearSession()
  }

  return {
    health: () => Promise.resolve({ status: 'ok', service: 'nestcms-portfolio-demo' }),
    login: async (email: string, password: string) => {
      const response = await demo.login(email, password)
      setSession(response)
      return response
    },
    requestMagicLink: async (_email?: string) => demo.requestMagicLink(),
    consumeMagicLink: async (_token?: string) => {
      const response = await demo.consumeMagicLink()
      setSession(response)
      return response
    },
    refresh: async () => {
      const refreshToken = session.refreshToken.value
      if (!refreshToken) {
        throw new Error('Sessao demo sem token de refresh.')
      }

      const response = await demo.refresh(refreshToken, session.user.value)
      setSession(response)
      return response
    },
    me: async () => {
      const response = await demo.me(session.accessToken.value, session.user.value) as { user: AuthUser }
      session.setUser(response.user)
      return response
    },
    logout: async () => {
      const response = await demo.logout()
      clearSession()
      return response
    },
    dashboard: () => demo.dashboard(),
    products: () => demo.products(),
    publicProducts: () => demo.publicProducts(),
    createProduct: (payload: Record<string, unknown>) => demo.createProduct(payload),
    orders: () => demo.orders(),
    updateOrderStatus: (orderId: number, status: string) => demo.updateOrderStatus(orderId, status),
    createPaymentRefund: (orderId: number, _payload?: Record<string, unknown>) => demo.createPaymentRefund(orderId),
    submitPaymentReview: (orderId: number, payload: Record<string, unknown>) => demo.submitPaymentReview(orderId, payload),
    pendingPaymentReport: (minutes: number = 60) => demo.pendingPaymentReport(minutes),
    abandonedCarts: () => demo.abandonedCarts(),
    sendRecovery: (cartId: number) => demo.sendRecovery(cartId),
    checkout: (payload: Record<string, unknown>) => demo.checkout(payload),
    revenue: () => demo.revenue(),
    resetDemo: () => demo.resetDemo()
  }
}
