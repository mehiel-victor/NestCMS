<script setup lang="ts">
import { BarChart3, Package, ShoppingCart } from '@lucide/vue'
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <NuxtLink to="/" class="brand" aria-label="NestCMS">
        <span class="brand-mark">N</span>
        <span>NestCMS</span>
      </NuxtLink>

      <nav class="nav-list" aria-label="Principal">
        <NuxtLink to="/" class="nav-link">
          <BarChart3 :size="18" aria-hidden="true" />
          <span>Dashboard</span>
        </NuxtLink>
        <NuxtLink to="/catalog" class="nav-link">
          <Package :size="18" aria-hidden="true" />
          <span>Catalogo</span>
        </NuxtLink>
        <NuxtLink to="/checkout" class="nav-link">
          <ShoppingCart :size="18" aria-hidden="true" />
          <span>Checkout</span>
        </NuxtLink>
      </nav>

      <p class="sidebar-foot">
        MVP publico para marcas DTC: catalogo, estoque, pedidos, recuperacao e receita em um fluxo.
      </p>
    </aside>

    <main class="main">
      <slot />
    </main>
  </div>
</template>
