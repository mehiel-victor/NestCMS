<script setup lang="ts">
import { CircleAlert, CircleCheck, Loader2 } from '@lucide/vue'

definePageMeta({
  requiresAuth: false
})

const api = useNestApi()
const route = useRoute()

const loading = ref(true)
const error = ref('')
const success = ref(false)

const token = computed(() => {
  const raw = route.query.token
  return typeof raw === 'string' ? raw.trim() : ''
})

const nextPath = computed(() => {
  const queryNext = route.query.next
  if (typeof queryNext === 'string' && queryNext.trim() !== '') {
    return queryNext
  }

  const pending = process.client ? localStorage.getItem('nestcms_auth_next') : '/'
  if (process.client && pending) {
    localStorage.removeItem('nestcms_auth_next')
  }

  return pending && pending.startsWith('/') ? pending : '/'
})

onMounted(async () => {
  if (!token.value) {
    error.value = 'Token demo ausente ou invalido.'
    loading.value = false
    return
  }

  try {
    await api.consumeMagicLink(token.value)
    success.value = true
    await navigateTo(nextPath.value)
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Falha na autenticacao.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="auth-page">
    <section class="auth-card auth-callback">
      <p class="auth-eyebrow">Sessao demo</p>
      <h1 class="auth-title">Validando acesso simulado</h1>

      <div v-if="loading" class="auth-callback-state">
        <Loader2 class="spin" :size="26" aria-hidden="true" />
        <p>Validando token local da demo...</p>
      </div>

      <div v-else-if="success" class="auth-callback-state">
        <CircleCheck :size="28" aria-hidden="true" />
        <p>Acesso demo validado. Direcionando para o painel...</p>
      </div>

      <div v-else class="auth-callback-state">
        <CircleAlert :size="28" aria-hidden="true" />
        <p class="error">{{ error }}</p>
        <NuxtLink to="/login">Voltar ao login</NuxtLink>
      </div>
    </section>
  </main>
</template>
