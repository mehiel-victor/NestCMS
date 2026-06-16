<script setup lang="ts">
const props = defineProps<{
  kpis?: Record<string, number>
}>()

const { currency, number, percent } = useFormatters()

const items = computed(() => [
  {
    label: 'Receita do mes',
    value: currency(props.kpis?.month_revenue),
    note: `${percent(props.kpis?.revenue_delta_percent)} vs. periodo anterior`
  },
  {
    label: 'Pedidos hoje',
    value: number(props.kpis?.orders_today),
    note: `${number(props.kpis?.total_orders)} pedidos totais`
  },
  {
    label: 'Ticket medio',
    value: currency(props.kpis?.average_order_value),
    note: 'AOV operacional'
  },
  {
    label: 'Conversao',
    value: percent(props.kpis?.conversion_rate),
    note: 'visita para compra'
  },
  {
    label: 'Carrinhos',
    value: number(props.kpis?.abandoned_carts),
    note: 'aptos para recuperacao'
  }
])
</script>

<template>
  <section class="kpi-grid" aria-label="Indicadores principais">
    <article v-for="item in items" :key="item.label" class="kpi">
      <p class="kpi-label">{{ item.label }}</p>
      <p class="kpi-value">{{ item.value }}</p>
      <p class="kpi-note">{{ item.note }}</p>
    </article>
  </section>
</template>

