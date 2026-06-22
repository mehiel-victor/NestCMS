<script setup lang="ts">
import { computed, ref } from 'vue'
import { BarChart3, Eye, LogOut, Package, ShoppingCart, UserCircle2 } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'

const api = useNestApi()
const session = useAuthSession()
const isLoggingOut = ref(false)

session.hydrate()

const hasSession = computed(() => !!session.accessToken.value && !!session.user.value)
const userEmail = computed(() => session.user.value?.email ?? '')
const userRole = computed(() => session.user.value?.role ?? '')

const logout = async () => {
  isLoggingOut.value = true
  try {
    await api.logout()
  } finally {
    isLoggingOut.value = false
    await navigateTo('/login')
  }
}
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <NuxtLink to="/" class="brand" aria-label="NestCMS">
        <span class="brand-mark">N</span>
        <span>NestCMS</span>
      </NuxtLink>

      <nav class="nav-list" aria-label="Principal">
        <NuxtLink to="/demo" class="nav-link">
          <Eye :size="18" aria-hidden="true" />
          <span>Demo</span>
        </NuxtLink>
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
          <span>Checkout demo</span>
        </NuxtLink>
      </nav>

      <div v-if="hasSession" class="sidebar-user">
        <UserCircle2 :size="18" aria-hidden="true" />
        <div>
          <p class="sidebar-user-email">{{ userEmail }}</p>
          <p class="sidebar-user-role">{{ userRole }}</p>
        </div>
        <CButton color-scheme="green" size="sm" :is-loading="isLoggingOut" @click="logout">
          <span class="icon-label">
            <LogOut :size="14" aria-hidden="true" />
            Sair
          </span>
        </CButton>
      </div>

      <p v-else class="sidebar-foot">
        <NuxtLink to="/login" class="login-link">Acessar demo</NuxtLink>
      </p>
    </aside>

    <main class="main">
      <slot />
    </main>
  </div>
</template>
