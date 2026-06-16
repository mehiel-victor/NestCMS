<script setup lang="ts">
const props = defineProps<{
  analytics?: {
    series?: Array<{ date: string; revenue: number; orders: number }>
    best_sellers?: Array<Record<string, unknown>>
    traffic_sources?: Array<Record<string, unknown>>
  }
}>()

const { currency, number } = useFormatters()

const maxRevenue = computed(() => {
  const values = props.analytics?.series?.map((item) => item.revenue) || [1]
  return Math.max(...values, 1)
})
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Receita e trafego</h2>
        <p class="panel-kicker">Serie diaria, origem e produtos que puxam margem.</p>
      </div>
    </div>

    <div class="chart-bars" aria-label="Receita diaria dos ultimos 14 dias">
      <div
        v-for="point in props.analytics?.series || []"
        :key="point.date"
        class="bar"
        :title="`${point.date}: ${currency(point.revenue)}`"
        :style="{ height: `${Math.max(8, (point.revenue / maxRevenue) * 180)}px` }"
      />
    </div>

    <div class="grid two" style="margin-top: 16px">
      <div>
        <h3 class="panel-title">Mais vendidos</h3>
        <div class="metric-list">
          <div v-for="item in props.analytics?.best_sellers || []" :key="String(item.sku)" class="metric-row">
            <div>
              <strong>{{ item.product_title }}</strong>
              <div class="muted">{{ item.sku }} · {{ number(Number(item.units)) }} un.</div>
            </div>
            <strong>{{ currency(Number(item.revenue)) }}</strong>
          </div>
        </div>
      </div>

      <div>
        <h3 class="panel-title">UTM</h3>
        <div class="metric-list">
          <div v-for="source in props.analytics?.traffic_sources || []" :key="`${source.source}-${source.medium}`" class="metric-row">
            <div>
              <strong>{{ source.source }}</strong>
              <div class="muted">{{ source.medium }} · {{ number(Number(source.visits)) }} visitas</div>
            </div>
            <strong>{{ currency(Number(source.revenue)) }}</strong>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
