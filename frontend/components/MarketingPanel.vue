<script setup lang="ts">
import { Send } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'
import type { AbandonedCart } from '~/types'

defineProps<{
  carts: AbandonedCart[]
}>()

const emit = defineEmits<{
  sent: []
}>()

const api = useNestApi()
const { currency, shortDate } = useFormatters()
const sending = ref<number | null>(null)
const error = ref('')

const send = async (cart: AbandonedCart) => {
  sending.value = cart.id
  error.value = ''

  try {
    await api.sendRecovery(cart.id)
    emit('sent')
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Nao foi possivel simular recuperacao.'
  } finally {
    sending.value = null
  }
}
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Recuperacao demo</h2>
        <p class="panel-kicker">Carrinhos mock elegiveis para simulacao de envio.</p>
      </div>
      <span class="status warn">{{ carts.length }} filas</span>
    </div>

    <div class="metric-list">
      <div v-if="error" class="notice error">{{ error }}</div>
      <div v-for="cart in carts" :key="cart.id" class="metric-row">
        <div>
          <strong>{{ cart.email }}</strong>
          <div class="muted">
            {{ currency(cart.cart_total) }} · {{ cart.utm_source || 'direct' }} · {{ shortDate(cart.updated_at) }}
          </div>
          <div v-if="cart.last_recovery_sent_at" class="muted">
            Simulado em {{ shortDate(cart.last_recovery_sent_at) }}
          </div>
        </div>
        <CButton size="sm" color-scheme="orange" :is-loading="sending === cart.id" @click="send(cart)">
          <span class="icon-label">
            <Send :size="15" aria-hidden="true" />
            Simular envio
          </span>
        </CButton>
      </div>
      <div v-if="!carts.length" class="notice">Nenhum carrinho demo esta elegivel para recuperacao agora.</div>
    </div>
  </section>
</template>
