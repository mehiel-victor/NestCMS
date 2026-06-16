<script setup lang="ts">
import { RefreshCw } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'
import type { Order } from '~/types'

const props = defineProps<{
  orders: Order[]
}>()

const emit = defineEmits<{
  refresh: []
}>()

const api = useNestApi()
const { currency, shortDate } = useFormatters()
const updating = ref<number | null>(null)

const statuses = ['received', 'processing', 'shipped', 'delivered', 'returned']

const nextStatus = (status: string) => {
  const index = statuses.indexOf(status)
  return statuses[Math.min(index + 1, statuses.length - 1)]
}

const advance = async (order: Order) => {
  const next = nextStatus(order.status)
  if (next === order.status) return
  updating.value = order.id
  await api.updateOrderStatus(order.id, next)
  updating.value = null
  emit('refresh')
}
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Pedidos</h2>
        <p class="panel-kicker">Fluxo recebido, processamento, envio, entrega e devolucao.</p>
      </div>
      <span class="status">{{ props.orders.length }} recentes</span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Status</th>
            <th>Total</th>
            <th>Origem</th>
            <th>Data</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in props.orders" :key="order.id">
            <td>
              <strong>#{{ order.id }} · {{ order.customer_name }}</strong>
              <div class="muted">{{ order.email }}</div>
            </td>
            <td><span class="status">{{ order.status }}</span></td>
            <td class="money">{{ currency(order.total) }}</td>
            <td>{{ order.utm_source || 'direct' }}</td>
            <td>{{ shortDate(order.created_at) }}</td>
            <td>
              <CButton
                size="sm"
                color-scheme="green"
                :is-loading="updating === order.id"
                :disabled="order.status === 'returned' || order.status === 'delivered'"
                @click="advance(order)"
              >
                <span class="icon-label">
                  <RefreshCw :size="15" aria-hidden="true" />
                  Avancar
                </span>
              </CButton>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
