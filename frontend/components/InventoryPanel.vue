<script setup lang="ts">
import type { LowStockItem } from '~/types'

defineProps<{
  lowStock: LowStockItem[]
  movements?: Array<Record<string, unknown>>
}>()
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Estoque</h2>
        <p class="panel-kicker">SKUs abaixo do minimo e ultimas movimentacoes.</p>
      </div>
      <span :class="lowStock.length ? 'status danger' : 'status'">{{ lowStock.length }} alertas</span>
    </div>

    <div class="metric-list">
      <div v-for="item in lowStock" :key="item.variant_id" class="metric-row">
        <div>
          <strong>{{ item.product_title }}</strong>
          <div class="muted">{{ item.sku }} · {{ item.option_value }}</div>
        </div>
        <div>
          <strong>{{ item.quantity }}</strong>
          <span class="muted"> / min. {{ item.low_stock_threshold }}</span>
        </div>
      </div>
      <div v-if="!lowStock.length" class="notice">Todos os SKUs estao acima do limite configurado.</div>
    </div>

    <div v-if="movements?.length" class="metric-list" style="margin-top: 16px">
      <div v-for="movement in movements" :key="String(movement.id)" class="metric-row">
        <div>
          <strong>{{ movement.sku }}</strong>
          <div class="muted">{{ movement.reason }} · {{ movement.warehouse }}</div>
        </div>
        <strong>{{ movement.delta_quantity }}</strong>
      </div>
    </div>
  </section>
</template>

