<script setup lang="ts">
import type { Product } from '~/types'

defineProps<{
  products: Product[]
}>()

const { currency } = useFormatters()

const visibilityClass = (visibility: string) => {
  if (visibility === 'draft') return 'status warn'
  if (visibility === 'scheduled') return 'status'
  return 'status'
}
</script>

<template>
  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Catalogo</h2>
        <p class="panel-kicker">Produtos, variantes, midias e margem.</p>
      </div>
      <span class="status">{{ products.length }} itens</span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Produto</th>
            <th>Tipo</th>
            <th>Visibilidade</th>
            <th>Preco</th>
            <th>Variantes</th>
            <th>Margem</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id">
            <td>
              <strong>{{ product.title }}</strong>
              <div class="muted">{{ product.category_name || 'Sem categoria' }} · {{ product.collection_name || 'Sem colecao' }}</div>
            </td>
            <td>{{ product.product_type }}</td>
            <td><span :class="visibilityClass(product.visibility)">{{ product.visibility }}</span></td>
            <td class="money">{{ currency(product.price) }}</td>
            <td>
              <div v-for="variant in product.variants" :key="variant.id">
                {{ variant.sku }} · {{ variant.option_value }} · {{ variant.stock }} un.
              </div>
            </td>
            <td>{{ product.margin_percent.toFixed(1) }}%</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

