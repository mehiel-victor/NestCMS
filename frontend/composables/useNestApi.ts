import type { AbandonedCart, Dashboard, Order, Product } from '~/types'

interface ApiList<T> {
  data: T
}

export function useNestApi() {
  const config = useRuntimeConfig()
  const apiBase = String(config.public.apiBase).replace(/\/$/, '')

  const request = <T>(path: string, options: Record<string, unknown> = {}) => {
    return $fetch<T>(`${apiBase}${path}`, options)
  }

  return {
    health: () => request<{ status: string; service: string }>('/health'),
    dashboard: () => request<Dashboard>('/api/dashboard'),
    products: async () => (await request<ApiList<Product[]>>('/api/products')).data,
    createProduct: async (payload: Record<string, unknown>) =>
      (await request<ApiList<Product>>('/api/products', { method: 'POST', body: payload })).data,
    orders: async () => (await request<ApiList<Order[]>>('/api/orders')).data,
    updateOrderStatus: async (orderId: number, status: string) =>
      (await request<ApiList<Order>>(`/api/orders/${orderId}/status`, { method: 'PATCH', body: { status } })).data,
    abandonedCarts: async () => (await request<ApiList<AbandonedCart[]>>('/api/marketing/abandoned-carts')).data,
    sendRecovery: async (cartId: number) =>
      request<ApiList<Record<string, unknown>>>(`/api/marketing/abandoned-carts/${cartId}/send`, { method: 'POST' }),
    checkout: async (payload: Record<string, unknown>) =>
      (await request<ApiList<Record<string, unknown>>>('/api/checkout', { method: 'POST', body: payload })).data,
    revenue: async () => (await request<ApiList<Record<string, unknown>>>('/api/analytics/revenue')).data
  }
}

