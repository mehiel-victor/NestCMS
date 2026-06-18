export type Money = number

export interface Variant {
  id: number
  product_id?: number
  sku: string
  option_name: string
  option_value: string
  price: Money
  stock: number
  low_stock_threshold: number
  is_digital: boolean
}

export interface Product {
  id: number
  title: string
  slug: string
  description: string
  product_type: 'physical' | 'digital' | 'bundle'
  visibility: 'draft' | 'published' | 'scheduled'
  price: Money
  compare_at_price?: Money | null
  margin_percent: number
  category_id?: number | null
  category_name?: string | null
  collection_id?: number | null
  collection_name?: string | null
  custom_fields: Record<string, unknown>
  variants: Variant[]
  media: Array<Record<string, unknown>>
}

export interface OrderItem {
  id: number
  product_title: string
  sku: string
  quantity: number
  unit_price: Money
  total: Money
}

export interface Order {
  id: number
  email: string
  customer_name: string
  status: 'received' | 'processing' | 'shipped' | 'delivered' | 'returned'
  payment_method: string
  shipping_method: string
  payment_status?: 'pending' | 'processing' | 'approved' | 'failed' | 'partially_refunded' | 'refunded' | 'chargeback' | null
  payment_provider?: string | null
  payment_provider_status?: string | null
  payment_transaction_id?: number | null
  subtotal: Money
  discount_total: Money
  shipping_total: Money
  total: Money
  utm_source?: string | null
  created_at: string
  items: OrderItem[]
}

export interface LowStockItem {
  variant_id: number
  product_id: number
  product_title: string
  sku: string
  option_name: string
  option_value: string
  low_stock_threshold: number
  quantity: number
  locations: Array<{ warehouse: string; code: string; quantity: number }>
}

export interface Dashboard {
  kpis: Record<string, number>
  low_stock: LowStockItem[]
  recent_orders: Order[]
  inventory_movements: Array<Record<string, unknown>>
  analytics: {
    series: Array<{ date: string; revenue: Money; orders: number }>
    best_sellers: Array<Record<string, unknown>>
    traffic_sources: Array<Record<string, unknown>>
  }
}

export interface AbandonedCart {
  id: number
  email: string
  status: string
  coupon_code?: string | null
  recovery_token: string
  utm_source?: string | null
  updated_at: string
  cart_total: Money
  last_recovery_sent_at?: string | null
  items: Array<{ id: number; product_title: string; sku: string; quantity: number; unit_price: Money }>
}
