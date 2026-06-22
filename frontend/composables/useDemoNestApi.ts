import type { AbandonedCart, AuthUser, Dashboard, Order, Product, SessionPayload, UserRole } from '~/types'

interface DemoCredential {
  email: string
  password: string
  role: UserRole
  id: number
}

interface DemoState {
  products: Product[]
  orders: Order[]
  carts: AbandonedCart[]
}

const DEMO_STATE_KEY = 'nestcms_demo_state_v1'

const demoCredentials: DemoCredential[] = [
  { id: 1, email: 'admin@nestcms.test', password: 'Admin@123', role: 'admin' },
  { id: 2, email: 'operator@nestcms.test', password: 'Operator@123', role: 'operator' },
  { id: 3, email: 'finance@nestcms.test', password: 'Finance@123', role: 'finance' }
]

const allowedPaymentMethods = ['pix', 'credit_card', 'boleto']
const orderStatuses: Order['status'][] = ['received', 'processing', 'shipped', 'delivered', 'returned']
const terminalOrderStatuses: Order['status'][] = ['delivered', 'returned']

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value)) as T

const isoDaysAgo = (days: number) => new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString()

const makeToken = (prefix: string) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2)}`

const slugify = (value: string, fallback: string) => {
  const slug = value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return slug || fallback
}

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
  },
  {
    id: 4,
    title: 'Serum Calmante Beta',
    slug: 'serum-calmante-beta',
    description: 'Produto em rascunho para demonstrar que draft nao aparece no checkout publico.',
    product_type: 'physical',
    visibility: 'draft',
    price: 119.9,
    compare_at_price: null,
    margin_percent: 57.8,
    category_id: 1,
    category_name: 'Cuidados',
    collection_id: 3,
    collection_name: 'Labs',
    custom_fields: { fase: 'beta' },
    variants: [
      {
        id: 5,
        product_id: 4,
        sku: 'SERUM-BETA-30',
        option_name: 'Volume',
        option_value: '30ml',
        price: 119.9,
        stock: 12,
        low_stock_threshold: 5,
        is_digital: false
      }
    ],
    media: [{ media_type: 'image', url: '/demo/serum-beta.jpg', alt_text: 'Serum Calmante Beta' }]
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
    payment_provider_status: 'pix_simulado',
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
    payment_provider_status: 'cartao_simulado',
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

const readStoredState = (): DemoState | null => {
  if (!process.client) {
    return null
  }

  const raw = localStorage.getItem(DEMO_STATE_KEY)
  if (!raw) {
    return null
  }

  try {
    const parsed = JSON.parse(raw) as Partial<DemoState>
    if (Array.isArray(parsed.products) && Array.isArray(parsed.orders) && Array.isArray(parsed.carts)) {
      return parsed as DemoState
    }
  } catch {
    localStorage.removeItem(DEMO_STATE_KEY)
  }

  return null
}

const seededState = (): DemoState => ({
  products: initialProducts(),
  orders: initialOrders(),
  carts: initialCarts()
})

export function useDemoNestApi() {
  const stored = readStoredState()
  const products = useState<Product[]>('demo.products', () => clone(stored?.products ?? initialProducts()))
  const orders = useState<Order[]>('demo.orders', () => clone(stored?.orders ?? initialOrders()))
  const carts = useState<AbandonedCart[]>('demo.carts', () => clone(stored?.carts ?? initialCarts()))

  const persist = () => {
    if (!process.client) {
      return
    }

    localStorage.setItem(DEMO_STATE_KEY, JSON.stringify({
      products: products.value,
      orders: orders.value,
      carts: carts.value
    }))
  }

  const sessionPayload = (user: AuthUser): SessionPayload => ({
    user,
    access_token: makeToken('demo-access'),
    refresh_token: makeToken('demo-refresh'),
    access_expires_at: new Date(Date.now() + 15 * 60 * 1000).toISOString(),
    refresh_expires_at: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
    session_id: makeToken('demo-session')
  })

  const buildBestSellers = () => {
    const rows = new Map<string, { product_title: string; sku: string; units: number; revenue: number }>()

    orders.value.forEach((order) => {
      order.items.forEach((item) => {
        const current = rows.get(item.sku) ?? { product_title: item.product_title, sku: item.sku, units: 0, revenue: 0 }
        current.units += item.quantity
        current.revenue += item.total
        rows.set(item.sku, current)
      })
    })

    return Array.from(rows.values()).sort((a, b) => b.revenue - a.revenue)
  }

  const buildTrafficSources = () => {
    const mediumBySource: Record<string, string> = {
      instagram: 'paid_social',
      google: 'cpc',
      email: 'owned',
      direct: 'direct'
    }

    const rows = new Map<string, { source: string; medium: string; visits: number; revenue: number }>()
    orders.value.forEach((order) => {
      const source = order.utm_source || 'direct'
      const current = rows.get(source) ?? { source, medium: mediumBySource[source] ?? 'referral', visits: 0, revenue: 0 }
      current.visits += Math.max(120, order.items.length * 80)
      current.revenue += order.total
      rows.set(source, current)
    })

    return Array.from(rows.values()).sort((a, b) => b.revenue - a.revenue)
  }

  const buildDashboard = (): Dashboard => {
    const currentOrders = orders.value
    const currentProducts = products.value
    const today = new Date().toISOString().slice(0, 10)
    const todayOrders = currentOrders.filter((order) => order.created_at.slice(0, 10) === today)
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
        orders_today: todayOrders.length,
        total_orders: orderCount,
        average_order_value: orderCount ? monthRevenue / orderCount : 0,
        conversion_rate: 4.8,
        abandoned_carts: carts.value.length
      },
      low_stock: lowStock,
      recent_orders: clone(currentOrders),
      inventory_movements: lowStock.map((item, index) => ({
        id: index + 1,
        sku: item.sku,
        reason: 'demo_stock_snapshot',
        warehouse: 'Principal',
        delta_quantity: item.quantity - item.low_stock_threshold
      })),
      analytics: {
        series: [
          { date: isoDaysAgo(6).slice(0, 10), revenue: 420, orders: 3 },
          { date: isoDaysAgo(5).slice(0, 10), revenue: 260, orders: 2 },
          { date: isoDaysAgo(4).slice(0, 10), revenue: 590, orders: 4 },
          { date: isoDaysAgo(3).slice(0, 10), revenue: 310, orders: 2 },
          { date: isoDaysAgo(2).slice(0, 10), revenue: 740, orders: 5 },
          { date: isoDaysAgo(1).slice(0, 10), revenue: currentOrders.filter((order) => order.created_at.slice(0, 10) === isoDaysAgo(1).slice(0, 10)).reduce((sum, order) => sum + order.total, 0), orders: currentOrders.filter((order) => order.created_at.slice(0, 10) === isoDaysAgo(1).slice(0, 10)).length },
          { date: today, revenue: todayOrders.reduce((sum, order) => sum + order.total, 0), orders: todayOrders.length }
        ],
        best_sellers: buildBestSellers(),
        traffic_sources: buildTrafficSources()
      }
    }
  }

  const requireDemoUser = (accessToken: string | null, user: AuthUser | null) => {
    if (!accessToken?.startsWith('demo-access') || !user) {
      throw new Error('Sessao demo expirada. Entre novamente.')
    }

    return user
  }

  const resetDemo = async () => {
    const nextState = seededState()
    products.value = nextState.products
    orders.value = nextState.orders
    carts.value = nextState.carts
    persist()
    return clone(nextState)
  }

  return {
    login: async (email: string, password: string) => {
      const credential = demoCredentials.find((item) => item.email === email.trim().toLowerCase() && item.password === password)
      if (!credential) {
        throw new Error('Credenciais invalidas para a sessao demo.')
      }

      return sessionPayload({
        id: credential.id,
        email: credential.email,
        role: credential.role
      })
    },
    requestMagicLink: async () => ({
      message: 'Sessao demo: use um dos perfis seedados. Nenhum e-mail real sera enviado.'
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
      const title = String(payload.title ?? '').trim()
      if (!title) {
        throw new Error('Informe um titulo para criar o produto demo.')
      }

      const variantPayload = Array.isArray(payload.variants) && payload.variants[0]
        ? (payload.variants[0] as Record<string, unknown>)
        : {}
      const sku = String(variantPayload.sku ?? '').trim()
      if (!sku) {
        throw new Error('Informe um SKU para a variante demo.')
      }

      const duplicatedSku = products.value.some((product) =>
        product.variants.some((variant) => variant.sku.toLowerCase() === sku.toLowerCase())
      )
      if (duplicatedSku) {
        throw new Error('Este SKU ja existe na sessao demo. Use outro codigo para continuar.')
      }

      const productType = ['physical', 'digital', 'bundle'].includes(String(payload.product_type))
        ? payload.product_type as Product['product_type']
        : 'physical'
      const visibility = ['draft', 'published', 'scheduled'].includes(String(payload.visibility))
        ? payload.visibility as Product['visibility']
        : 'published'
      const price = Number(payload.price ?? variantPayload.price ?? 0)
      if (!Number.isFinite(price) || price <= 0) {
        throw new Error('Informe um preco maior que zero para o produto demo.')
      }

      const nextId = Math.max(...products.value.map((product) => product.id), 0) + 1
      const variantId = Math.max(...products.value.flatMap((product) => product.variants.map((variant) => variant.id)), 0) + 1
      const isDigital = productType === 'digital' || Boolean(variantPayload.is_digital)
      const product: Product = {
        id: nextId,
        title,
        slug: slugify(title, `produto-${nextId}`),
        description: String(payload.description || 'Produto criado na sessao demo.'),
        product_type: productType,
        visibility,
        price,
        compare_at_price: payload.compare_at_price === null ? null : Number(payload.compare_at_price || 0) || null,
        margin_percent: Number(payload.margin_percent || 54),
        category_id: Number(payload.category_id || 1),
        category_name: 'Demo',
        collection_id: Number(payload.collection_id || 1),
        collection_name: 'Criados no painel',
        custom_fields: (payload.custom_fields as Record<string, unknown>) || {},
        variants: [
          {
            id: variantId,
            product_id: nextId,
            sku,
            option_name: String(variantPayload.option_name || 'Opcao'),
            option_value: String(variantPayload.option_value || 'Padrao'),
            price,
            stock: isDigital ? 999 : Math.max(0, Number(variantPayload.stock ?? 0)),
            low_stock_threshold: Math.max(0, Number(variantPayload.low_stock_threshold || 5)),
            is_digital: isDigital
          }
        ],
        media: Array.isArray(payload.media) ? (payload.media as Array<Record<string, unknown>>) : []
      }

      products.value = [product, ...products.value]
      persist()
      return clone(product)
    },
    orders: async () => clone(orders.value),
    updateOrderStatus: async (orderId: number, status: string) => {
      if (!orderStatuses.includes(status as Order['status'])) {
        throw new Error('Status operacional invalido para a sessao demo.')
      }

      const current = orders.value.find((order) => order.id === orderId)
      if (!current) {
        throw new Error('Pedido demo nao encontrado.')
      }

      if (terminalOrderStatuses.includes(current.status)) {
        throw new Error('Este pedido ja esta em um status operacional final.')
      }

      orders.value = orders.value.map((order) =>
        order.id === orderId ? { ...order, status: status as Order['status'] } : order
      )
      persist()
      return clone(orders.value.find((order) => order.id === orderId) as Order)
    },
    createPaymentRefund: async (orderId: number) => {
      const current = orders.value.find((order) => order.id === orderId)
      if (!current) {
        throw new Error('Pedido demo nao encontrado.')
      }

      orders.value = orders.value.map((order) =>
        order.id === orderId
          ? { ...order, payment_status: 'partially_refunded', payment_provider_status: 'reembolso_simulado' }
          : order
      )
      persist()
      return { order_id: orderId, status: 'partially_refunded', simulated: true }
    },
    submitPaymentReview: async (orderId: number, payload: Record<string, unknown>) => {
      const current = orders.value.find((order) => order.id === orderId)
      if (!current) {
        throw new Error('Pedido demo nao encontrado.')
      }

      const status = payload.decision === 'chargeback' ? 'chargeback' : 'approved'
      orders.value = orders.value.map((order) =>
        order.id === orderId ? { ...order, payment_status: status as Order['payment_status'], payment_provider_status: `${status}_simulado` } : order
      )
      persist()
      return { order_id: orderId, decision: payload.decision, status, simulated: true }
    },
    pendingPaymentReport: async (minutes = 60) => ({
      minutes,
      pending_orders: orders.value.filter((order) => order.payment_status === 'pending' || order.payment_status === 'processing').length,
      generated_at: new Date().toISOString()
    }),
    abandonedCarts: async () => clone(carts.value),
    sendRecovery: async (cartId: number) => {
      const current = carts.value.find((cart) => cart.id === cartId)
      if (!current) {
        throw new Error('Carrinho demo nao encontrado.')
      }

      carts.value = carts.value.map((cart) =>
        cart.id === cartId ? { ...cart, status: 'recovery_simulated', last_recovery_sent_at: new Date().toISOString() } : cart
      )
      persist()
      return { status: true, cart_id: cartId, simulated: true }
    },
    checkout: async (payload: Record<string, unknown>) => {
      const items = Array.isArray(payload.items) ? (payload.items as Array<Record<string, unknown>>) : []
      const firstItem = items[0] || {}
      const variantId = Number(firstItem.variant_id || 0)
      const quantity = Number(firstItem.quantity || 0)

      if (!Number.isInteger(quantity) || quantity <= 0) {
        throw new Error('Informe uma quantidade valida para simular o checkout.')
      }

      const product = products.value.find((candidate) =>
        candidate.visibility === 'published' && candidate.variants.some((variant) => variant.id === variantId)
      )
      const variant = product?.variants.find((candidate) => candidate.id === variantId)
      if (!product || !variant) {
        throw new Error('Produto demo indisponivel para checkout publico.')
      }

      if (!variant.is_digital && quantity > variant.stock) {
        throw new Error('Estoque demo insuficiente para esta quantidade.')
      }

      const paymentMethod = String(payload.payment_method || 'pix')
      if (!allowedPaymentMethods.includes(paymentMethod)) {
        throw new Error('Escolha PIX, cartao de credito ou boleto para simular o pagamento.')
      }

      const customer = (payload.customer || {}) as Record<string, unknown>
      const subtotal = variant.price * quantity
      const discount = String(payload.coupon_code || '').trim().toUpperCase() === 'WELCOME10' ? subtotal * 0.1 : 0
      const shippingMethod = String(payload.shipping_method || 'standard')
      const shipping = variant.is_digital ? 0 : shippingMethod === 'express' ? 29.9 : shippingMethod === 'pickup' ? 0 : 18.9
      const total = subtotal - discount + shipping
      const nextOrderId = Math.max(...orders.value.map((order) => order.id), 1000) + 1
      const paymentStatus = paymentMethod === 'credit_card' ? 'approved' : 'processing'
      const instructions: Record<string, string> = {
        pix: 'PIX simulado: nenhum QR Code real foi gerado e nenhuma cobranca foi criada.',
        credit_card: 'Cartao simulado: nenhum numero de cartao foi solicitado ou processado.',
        boleto: 'Boleto simulado: nenhuma linha digitavel real foi emitida.'
      }
      const order: Order = {
        id: nextOrderId,
        email: String(customer.email || 'cliente@example.com'),
        customer_name: String(customer.name || 'Cliente Demo'),
        status: 'received',
        payment_method: paymentMethod,
        shipping_method: variant.is_digital ? 'digital' : shippingMethod,
        payment_status: paymentStatus,
        payment_provider: 'mock',
        payment_provider_status: `${paymentMethod}_simulado`,
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
            ? { ...candidateVariant, stock: candidateVariant.stock - quantity }
            : candidateVariant
        )
      }))
      orders.value = [order, ...orders.value]
      persist()

      return {
        order_id: order.id,
        status: order.status,
        order,
        payment_status: order.payment_status,
        payment_method: order.payment_method,
        payment_provider: order.payment_provider,
        total,
        payment_instructions: instructions[paymentMethod]
      }
    },
    revenue: async () => ({
      month_revenue: buildDashboard().kpis.month_revenue,
      generated_at: new Date().toISOString()
    }),
    resetDemo
  }
}
