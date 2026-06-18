<script setup lang="ts">
import { ShoppingCart } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'
import { CInput } from '@chakra-ui/c-input'
import type { Product, Variant } from '~/types'

definePageMeta({
  requiresAuth: false
})

const api = useNestApi()
const { currency } = useFormatters()

const products = ref<Product[]>([])
const loading = ref(true)
const placing = ref(false)
const error = ref('')
const result = ref<Record<string, unknown> | null>(null)

const form = reactive({
  name: 'Ana Costa',
  email: 'ana@example.com',
  variant_id: 1,
  quantity: 1,
  payment_method: 'pix',
  shipping_method: 'standard',
  coupon_code: 'WELCOME10',
  utm_source: 'instagram'
})

const variants = computed<Array<Variant & { product_title: string }>>(() =>
  products.value.flatMap((product) =>
    product.variants.map((variant) => ({
      ...variant,
      product_title: product.title
    }))
  )
)

const selectedVariant = computed(() => variants.value.find((variant) => variant.id === Number(form.variant_id)))

const totalPreview = computed(() => {
  const subtotal = Number(selectedVariant.value?.price || 0) * Number(form.quantity || 1)
  const discount = form.coupon_code === 'WELCOME10' ? subtotal * 0.1 : 0
  const shipping = form.shipping_method === 'express' ? 29.9 : form.shipping_method === 'pickup' ? 0 : 18.9
  return Math.max(0, subtotal - discount + shipping)
})

const load = async () => {
  loading.value = true
  error.value = ''

  try {
    products.value = await api.publicProducts()
    if (variants.value[0]) {
      form.variant_id = variants.value[0].id
    }
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Falha ao carregar produtos.'
  } finally {
    loading.value = false
  }
}

const checkout = async () => {
  placing.value = true
  error.value = ''
  result.value = null

  try {
    result.value = await api.checkout({
      customer: {
        name: form.name,
        email: form.email
      },
      items: [
        {
          variant_id: Number(form.variant_id),
          quantity: Number(form.quantity)
        }
      ],
      payment_method: form.payment_method,
      shipping_method: form.shipping_method,
      coupon_code: form.coupon_code,
      utm_source: form.utm_source,
      create_account: false,
      upsell_ids: [2],
      cross_sell_ids: [3]
    })

    await load()
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Checkout recusado.'
  } finally {
    placing.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppShell>
    <div class="topbar">
      <div>
        <p class="eyebrow">Checkout</p>
        <h1 class="page-title">Compra como visitante</h1>
        <p class="page-subtitle">
          Checkout de uma pagina com metodo de pagamento simulado, cupom, frete e baixa de estoque.
        </p>
      </div>
    </div>

    <div class="grid two">
      <section class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Pedido</h2>
            <p class="panel-kicker">Sem conta previa e com criacao de cliente pos-compra.</p>
          </div>
          <ShoppingCart :size="20" aria-hidden="true" />
        </div>

        <form class="form-grid" @submit.prevent="checkout">
          <div class="field">
            <label for="name">Nome</label>
            <CInput id="name" v-model="form.name" />
          </div>

          <div class="field">
            <label for="email">E-mail</label>
            <CInput id="email" v-model="form.email" type="email" />
          </div>

          <div class="field full">
            <label for="variant">Produto</label>
            <select id="variant" v-model.number="form.variant_id">
              <option v-for="variant in variants" :key="variant.id" :value="variant.id">
                {{ variant.product_title }} · {{ variant.sku }} · {{ currency(variant.price) }} · {{ variant.stock }} un.
              </option>
            </select>
          </div>

          <div class="field">
            <label for="quantity">Quantidade</label>
            <input id="quantity" v-model.number="form.quantity" type="number" min="1" />
          </div>

          <div class="field">
            <label for="coupon">Cupom</label>
            <input id="coupon" v-model="form.coupon_code" />
          </div>

          <div class="field">
            <label for="payment">Pagamento</label>
            <select id="payment" v-model="form.payment_method">
              <option value="pix">PIX</option>
              <option value="credit_card">Cartao de credito</option>
              <option value="boleto">Boleto</option>
              <option value="apple_pay">Apple Pay</option>
              <option value="google_pay">Google Pay</option>
            </select>
          </div>

          <div class="field">
            <label for="shipping">Frete</label>
            <select id="shipping" v-model="form.shipping_method">
              <option value="standard">Padrao</option>
              <option value="express">Expresso</option>
              <option value="pickup">Retirada</option>
            </select>
          </div>

          <div class="field full">
            <label for="utm">Origem</label>
            <input id="utm" v-model="form.utm_source" />
          </div>

          <div class="split-actions full">
            <strong>Total previsto: {{ currency(totalPreview) }}</strong>
            <CButton color-scheme="green" type="submit" :is-loading="placing" :disabled="loading">
              <span class="icon-label">
                <ShoppingCart :size="16" aria-hidden="true" />
                Finalizar
              </span>
            </CButton>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Resultado</h2>
            <p class="panel-kicker">Pedido criado e estoque atualizado.</p>
          </div>
        </div>

        <div v-if="error" class="notice error">{{ error }}</div>
        <div v-else-if="result" class="metric-list">
          <div class="metric-row">
            <span>Pedido</span>
            <strong>#{{ result.order_id }}</strong>
          </div>
          <div class="metric-row">
            <span>Status</span>
            <span class="status">{{ result.status }}</span>
          </div>
          <div class="metric-row">
            <span>Pagamento</span>
            <strong>{{ result.payment_status || 'pendente' }}</strong>
          </div>
          <div class="metric-row">
            <span>Provedor</span>
            <strong>{{ result.provider || 'mock' }}</strong>
          </div>
          <div class="metric-row">
            <span>Total</span>
            <strong>{{ currency(Number(result.total)) }}</strong>
          </div>
          <div class="metric-row">
            <span>Pagamento</span>
            <strong>{{ result.payment_method }}</strong>
          </div>
          <div v-if="result.payment_instructions" class="metric-note">
            {{ typeof result.payment_instructions === 'string' ? result.payment_instructions : result.payment_instructions.instructions || result.payment_instructions.reference }}
          </div>
        </div>
        <div v-else class="notice">Aguardando pedido.</div>
      </section>
    </div>
  </AppShell>
</template>
