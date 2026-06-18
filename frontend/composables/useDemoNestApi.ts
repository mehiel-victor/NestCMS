import type { AbandonedCart, AuthUser, Dashboard, Order, Product, SessionPayload, UserRole } from '~/types'

interface DemoCredential {
  email: string
  password: string
  role: UserRole
  id: number
}

const demoCredentials: DemoCredential[] = [
  { id: 1, email: 'admin@nestcms.test', password: 'Admin@123', role: 'admin' },
  { id: 2, email: 'operator@nestcms.test', password: 'Operator@123', role: 'operator' },
  { id: 3, email: 'finance@nestcms.test', password: 'Finance@123', role: 'finance' }
]

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value)) as T

const isoDaysAgo = (days: number) => new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString()

const makeToken = (prefix: string) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2)}`

const initialProducts = (): Product[] => [
  {
    id: 1,
    title: 'Kit Ritual da Manha',
    slug: 'kit-ritual-da-manha',
    description: 'Bundle para rotina DTC com produto fisico e guia digital.',
    product_type: 'bundle',
    visibility: 'published',
    price: 149.9,
    compare_at_price: 189.9,
    margin_percent: 62.4,
    category_id: 1,
    category_name: 'Cuidados',
    collection_id: 1,
    collection_name: 'Essenciais',
    custom_fields: { material: 'algodao organico' },
    variants: [
      {
        id: 1,
        product_id: 1,
        sku: 'KIT-RITUAL-P',
        option_name: 'Tamanho',
        option_value: 'P',
        price: 149.9,
        stock: 6,
        low_stock_threshold: 8,
        is_digital: false
      },
      {
        id: 2,
        product_id: 1,
        sku: 'KIT-RITUAL-M',
        option_name: 'Tamanho',
        option_value: 'M',
        price: 149.9,
        stock: 18,
        low_stock_threshold: 8,
        is_digital: false
      }
    ],
    media: [{ media_type: 'image', url: '/demo/kit-ritual.jpg', alt_text: 'Kit Ritual da Manha' }]
  },
  {
    id: 2,
    title: 'Planner Digital DTC',
    slug: 'planner-digital-dtc',
    description: 'Arquivo digital para planejar lancamentos e reposicao de estoque.',
    product_type: 'digital',
    visibility: 'published',
    price: 49.9,
    compare_at_price: null,
    margin_percent: 88.2,
    category_id: 2,
    category_name: 'Digital',
    collection_id: 1,
    collection_name: 'Essenciais',
    custom_fields: { formato: 'pdf' },
    variants: [
      {
        id: 3,
        product_id: 2,
        sku: 'PLANNER-DTC',
        option_name: 'Licenca',
        option_value: 'Individual',
        price: 49.9,
        stock: 999,
        low_stock_threshold: 1,
        is_digital: true
      }
    ],
    media: [{ media_type: 'document', url: '/demo/planner.pdf', alt_text: 'Planner Digital DTC' }]
  },
  {
    id: 3,
    title: 'Refil Blend Energia',
    slug: 'refil-blend-energia',
    description: 'Refil fisico para recompra recorrente com margem alta.',
    product_type: 'physical',
    visibility: 'scheduled',
    price: 79.9,
    compare_at_price: 99.9,
    margin_percent: 55.1,
    category_id: 3,
    category_name: 'Reposicao',
    collection_id: 2,
    collection_name: 'Recorrencia',
    custom_fields: { peso: '300g' },
    variants: [
      {
        id: 4,
        product_id: 3,
        sku: 'REFIL-BLEND-300',
        option_name: 'Peso',
        option_value: '300g',
        price: 79.9,
        stock: 4,
        low_stock_threshold: 10,
        is_digital: false
      }
    ],
    media: [{ media_type: 'image', url: '/demo/refil-blend.jpg', alt_text: 'Refil Blend Energia' }]
  }
]

const initialOrders = (): Order[] => [
  {
    id: 1024,
    email: 'ana@example.com',
    customer_name: 'Ana Costa',
    status: 'processing',
    payment_method: 'pix',
    shipping_method: 'standard',
    payment_status: 'approved',
    payment_provider: 'mock',
    payment_provider_status: 'approved',
    payment_transaction_id: 501,
    subtotal: 149.9,
    discount_total: 14.99,
    shipping_total: 18.9,
    total: 153.81,
    utm_source: 'instagram',
    created_at: isoDaysAgo(0),
    items: [
      {
        id: 1,
        product_title: 'Kit Ritual da Manha',
        sku: 'KIT-RITUAL-P',
        quantity: 1,
        unit_price: 149.9,
        total: 149.9
      }
    ]
  },
  {
    id: 1023,
    email: 'bruno@example.com',
    customer_name: 'Bruno Lima',
    status: 'shipped',
    payment_method: 'credit_card',
    shipping_method: 'express',
    payment_status: 'approved',
    payment_provider: 'mock',
    payment_provider_status: 'captured',
    payment_transaction_id: 500,
    subtotal: 229.8,
    discount_total: 0,
    shipping_total: 29.9,
    total: 259.7,
    utm_source: 'google',
    created_at: isoDaysAgo(1),
    items: [
      {
        id: 2,
        product_title: 'Kit Ritual da Manha',
        sku: 'KIT-RITUAL-M',
        quantity: 1,
        unit_price: 149.9,
        total: 149.9
      },
      {
        id: 3,
        product_title: 'Refil Blend Energia',
        sku: 'REFIL-BLEND-300',
        quantity: 1,
        unit_price: 79.9,
        total: 79.9
      }
    ]
  }
]

const initialCarts = (): AbandonedCart[] => [
  {
    id: 301,
    email: 'julia@example.com',
    status: 'abandoned',
    coupon_code: 'WELCOME10',
    recovery_token: 'demo-recovery-julia',
    utm_source: 'instagram',
    updated_at: isoDaysAgo(1),
    cart_total: 149.9,
    last_recovery_sent_at: null,
    items: [
      {
        id: 1,
        product_title: 'Kit Ritual da Manha',
        sku: 'KIT-RITUAL-P',
        quantity: 1,
        unit_price: 149.9
      }
    ]
  },
  {
    id: 302,
    email: 'marina@example.com',
    status: 'abandoned',
    coupon_code: null,
    recovery_token: 'demo-recovery-marina',
    utm_source: 'email',
    updated_at: isoDaysAgo(2),
    cart_total: 49.9,
    last_recovery_sent_at: null,
    items: [
      {
        id: 2,
        product_title: 'Planner Digital DTC',
        sku: 'PLANNER-DTC',
        quantity: 1,
        unit_price: 49.9
      }
    ]
  }
]

export function useDemoNestApi() {
  const products = useState<Product[]>('demo.products', initialProducts)
  const orders = useState<Order[]>('demo.orders', initialOrders)
  const carts = useState<AbandonedCart[]>('demo.carts', initialCarts)

  const sessionPayload = (user: AuthUser): SessionPayload => ({
    user,
    access_token: makeToken('demo-access'),
    refresh_token: makeToken('demo-refresh'),
    access_expires_at: new Date(Date.now() + 15 * 60 * 1000).toISOString(),
    refresh_expires_at: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
    session_id: makeToken('demo-session')
  })

  const buildDashboard = (): Dashboard => {
    const currentOrders = orders.value
    const currentProducts = products.value
    const monthRevenue = currentOrders.reduce((sum, order) => sum + order.total, 0)
    const orderCount = currentOrders.length

    const lowStock = currentProducts.flatMap((product) =>
      product.variants
        .filter((variant) => !variant.is_digital && variant.stock <= variant.low_stock_threshold)
        .map((variant) => ({
          variant_id: variant.id,
          product_id: product.id,
          product_title: product.title,
          sku: variant.sku,
          option_name: variant.option_name,
          option_value: variant.option_value,
          low_stock_threshold: variant.low_stock_threshold,
          quantity: variant.stock,
          locations: [{ warehouse: 'Principal', code: 'WH-01', quantity: variant.stock }]
        }))
    )

    return {
      kpis: {
        month_revenue: monthRevenue,
        revenue_delta_percent: 18.6,
        orders_today: currentOrders.filter((order) => order.created_at.slice(0, 10) === new Date().toISOString().slice(0, 10)).length,
        total_orders: orderCount,
        average_order_value: orderCount ? monthRevenue / orderCount : 0,
        conversion_rate: 4.8,
        abandoned_carts: carts.value.length
      },
      low_stock: lowStock,
      recent_orders: clone(currentOrders),
      inventory_movements: [
        { id: 1, sku: 'KIT-RITUAL-P', reason: 'checkout', warehouse: 'Principal', delta_quantity: -1 },
        { id: 2, sku: 'REFIL-BLEND-300', reason: 'reorder_pending', warehouse: 'Principal', delta_quantity: -2 }
      ],
      analytics: {
        series: [
          { date: isoDaysAgo(6).slice(0, 10), revenue: 420, orders: 3 },
          { date: isoDaysAgo(5).slice(0, 10), revenue: 260, orders: 2 },
          { date: isoDaysAgo(4).slice(0, 10), revenue: 590, orders: 4 },
          { date: isoDaysAgo(3).slice(0, 10), revenue: 310, orders: 2 },
          { date: isoDaysAgo(2).slice(0, 10), revenue: 740, orders: 5 },
          { date: isoDaysAgo(1).slice(0, 10), revenue: 259.7, orders: 1 },
          { date: new Date().toISOString().slice(0, 10), revenue: 153.81, orders: 1 }
        ],
        best_sellers: [
          { product_title: 'Kit Ritual da Manha', sku: 'KIT-RITUAL-M', units: 18, revenue: 2698.2 },
          { product_title: 'Planner Digital DTC', sku: 'PLANNER-DTC', units: 14, revenue: 698.6 }
        ],
        traffic_sources: [
          { source: 'instagram', medium: 'paid_social', visits: 1240, revenue: 3180.4 },
          { source: 'google', medium: 'cpc', visits: 860, revenue: 1890.1 },
          { source: 'email', medium: 'owned', visits: 420, revenue: 920.5 }
        ]
      }
    }
  }

  const requireDemoUser = (accessToken: string | null, user: AuthUser | null) => {
    if (!accessToken?.startsWith('demo-access') || !user) {
      throw new Error('Sessao demo expirada. Entre novamente.')
    }

    return user
  }

  return {
    login: async (email: string, password: string) => {
      const credential = demoCredentials.find((item) => item.email === email.trim().toLowerCase() && item.password === password)
      if (!credential) {
        throw new Error('Credenciais invalidas para o modo demo.')
      }

      return sessionPayload({
        id: credential.id,
        email: credential.email,
        role: credential.role
      })
    },
    requestMagicLink: async () => ({
      message: 'Modo demo ativo. Use as credenciais seedadas para entrar.'
    }),
    consumeMagicLink: async () => sessionPayload({ id: 1, email: 'admin@nestcms.test', role: 'admin' }),
    refresh: async (refreshToken: string | null, user: AuthUser | null) => {
      if (!refreshToken?.startsWith('demo-refresh') || !user) {
        throw new Error('Sessao demo expirada. Entre novamente.')
      }

      return sessionPayload(user)
    },
    me: async (accessToken: string | null, user: AuthUser | null) => ({
      user: requireDemoUser(accessToken, user)
    }),
    logout: async () => ({ status: true }),
    dashboard: async () => clone(buildDashboard()),
    products: async () => clone(products.value),
    publicProducts: async () => clone(products.value.filter((product) => product.visibility === 'published')),
    createProduct: async (payload: Record<string, unknown>) => {
      const nextId = Math.max(...products.value.map((product) => product.id), 0) + 1
      const title = String(payload.title || 'Produto demo')
      const variantPayload = Array.isArray(payload.variants) && payload.variants[0]
        ? (payload.variants[0] as Record<string, unknown>)
        : {}
      const stock = Number(variantPayload.stock ?? 12)
      const price = Number(payload.price ?? variantPayload.price ?? 99.9)
      const variantId = Math.max(...products.value.flatMap((product) => product.variants.map((variant) => variant.id)), 0) + 1
      const product: Product = {
        id: nextId,
        title,
        slug: title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || `produto-${nextId}`,
        description: String(payload.description || 'Produto criado no modo demo.'),
        product_type: (payload.product_type as Product['product_type']) || 'physical',
        visibility: (payload.visibility as Product['visibility']) || 'published',
        price,
        compare_at_price: null,
        margin_percent: 54,
        category_id: Number(payload.category_id || 1),
        category_name: 'Demo',
        collection_id: Number(payload.collection_id || 1),
        collection_name: 'Criados no painel',
        custom_fields: (payload.custom_fields as Record<string, unknown>) || {},
        variants: [
          {
            id: variantId,
            product_id: nextId,
            sku: String(variantPayload.sku || `DEMO-${nextId}`),
            option_name: String(variantPayload.option_name || 'Opcao'),
            option_value: String(variantPayload.option_value || 'Padrao'),
            price,
            stock,
            low_stock_threshold: Number(variantPayload.low_stock_threshold || 5),
            is_digital: false
          }
        ],
        media: Array.isArray(payload.media) ? (payload.media as Array<Record<string, unknown>>) : []
      }

      products.value = [product, ...products.value]
      return clone(product)
    },
    orders: async () => clone(orders.value),
    updateOrderStatus: async (orderId: number, status: string) => {
      const nextOrders = orders.value.map((order) =>
        order.id === orderId ? { ...order, status: status as Order['status'] } : order
      )
      orders.value = nextOrders
      const updated = nextOrders.find((order) => order.id === orderId)
      if (!updated) {
        throw new Error('Pedido demo nao encontrado.')
      }

      return clone(updated)
    },
    createPaymentRefund: async (orderId: number) => {
      orders.value = orders.value.map((order) =>
        order.id === orderId ? { ...order, payment_status: 'partially_refunded' } : order
      )
      return { order_id: orderId, status: 'partially_refunded' }
    },
    submitPaymentReview: async (orderId: number, payload: Record<string, unknown>) => {
      const status = payload.decision === 'chargeback' ? 'chargeback' : 'approved'
      orders.value = orders.value.map((order) =>
        order.id === orderId ? { ...order, payment_status: status as Order['payment_status'], payment_provider_status: status } : order
      )
      return { order_id: orderId, decision: payload.decision, status }
    },
    pendingPaymentReport: async (minutes = 60) => ({
      minutes,
      pending_orders: orders.value.filter((order) => order.payment_status === 'pending').length,
      generated_at: new Date().toISOString()
    }),
    abandonedCarts: async () => clone(carts.value),
    sendRecovery: async (cartId: number) => {
      carts.value = carts.value.map((cart) =>
        cart.id === cartId ? { ...cart, last_recovery_sent_at: new Date().toISOString() } : cart
      )
      return { status: true, cart_id: cartId }
    },
    checkout: async (payload: Record<string, unknown>) => {
      const items = Array.isArray(payload.items) ? (payload.items as Array<Record<string, unknown>>) : []
      const firstItem = items[0] || {}
      const variantId = Number(firstItem.variant_id || 1)
      const quantity = Number(firstItem.quantity || 1)
      const product = products.value.find((candidate) => candidate.variants.some((variant) => variant.id === variantId))
      const variant = product?.variants.find((candidate) => candidate.id === variantId)
      if (!product || !variant) {
        throw new Error('Produto demo indisponivel para checkout.')
      }

      const customer = (payload.customer || {}) as Record<string, unknown>
      const subtotal = variant.price * quantity
      const discount = payload.coupon_code ? subtotal * 0.1 : 0
      const shippingMethod = String(payload.shipping_method || 'standard')
      const shipping = shippingMethod === 'express' ? 29.9 : shippingMethod === 'pickup' ? 0 : 18.9
      const total = subtotal - discount + shipping
      const nextOrderId = Math.max(...orders.value.map((order) => order.id), 1000) + 1
      const order: Order = {
        id: nextOrderId,
        email: String(customer.email || 'cliente@example.com'),
        customer_name: String(customer.name || 'Cliente Demo'),
        status: 'received',
        payment_method: String(payload.payment_method || 'pix'),
        shipping_method: shippingMethod,
        payment_status: 'approved',
        payment_provider: 'mock',
        payment_provider_status: 'approved',
        payment_transaction_id: nextOrderId + 1000,
        subtotal,
        discount_total: discount,
        shipping_total: shipping,
        total,
        utm_source: String(payload.utm_source || 'direct'),
        created_at: new Date().toISOString(),
        items: [
          {
            id: nextOrderId,
            product_title: product.title,
            sku: variant.sku,
            quantity,
            unit_price: variant.price,
            total: subtotal
          }
        ]
      }

      products.value = products.value.map((candidate) => ({
        ...candidate,
        variants: candidate.variants.map((candidateVariant) =>
          candidateVariant.id === variantId && !candidateVariant.is_digital
            ? { ...candidateVariant, stock: Math.max(0, candidateVariant.stock - quantity) }
            : candidateVariant
        )
      }))
      orders.value = [order, ...orders.value]

      return {
        order_id: order.id,
        order,
        payment_status: order.payment_status,
        payment_method: order.payment_method,
        payment_provider: order.payment_provider,
        total,
        payment_instructions: 'Pagamento aprovado no modo demo.'
      }
    },
    revenue: async () => ({
      month_revenue: buildDashboard().kpis.month_revenue,
      generated_at: new Date().toISOString()
    })
  }
}
