<script setup lang="ts">
import { RefreshCw } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'
import type { AbandonedCart, Dashboard } from '~/types'

definePageMeta({
  requiresAuth: true,
  allowedRoles: ['admin', 'operator', 'finance']
})

const api = useNestApi()
const dashboard = ref<Dashboard | null>(null)
const carts = ref<AbandonedCart[]>([])
const loading = ref(true)
const error = ref('')

const load = async () => {
  loading.value = true
  error.value = ''

  try {
    const [dashboardData, cartData] = await Promise.all([api.dashboard(), api.abandonedCarts()])
    dashboard.value = dashboardData
    carts.value = cartData
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Falha ao carregar dados.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppShell>
    <div class="topbar">
      <div>
        <p class="eyebrow">Operacao DTC</p>
        <h1 class="page-title">Painel da Maria</h1>
        <p class="page-subtitle">
          Catalogo, pedidos, estoque, recuperacao e receita em uma rotina unica para vender sem depender de uma loja engessada.
        </p>
      </div>

      <div class="toolbar">
        <CButton color-scheme="green" :is-loading="loading" @click="load">
          <span class="icon-label">
            <RefreshCw :size="16" aria-hidden="true" />
            Atualizar
          </span>
        </CButton>
      </div>
    </div>

    <div v-if="error" class="notice error">{{ error }}</div>
    <div v-else-if="loading" class="notice">Carregando operacao...</div>

    <div v-if="dashboard" class="grid">
      <KpiStrip :kpis="dashboard.kpis" />

      <div class="grid two">
        <InventoryPanel :low-stock="dashboard.low_stock" :movements="dashboard.inventory_movements" />
        <MarketingPanel :carts="carts" @sent="load" />
      </div>

      <OrderTimeline :orders="dashboard.recent_orders" @refresh="load" />
      <AnalyticsPanel :analytics="dashboard.analytics" />
    </div>
  </AppShell>
</template>
