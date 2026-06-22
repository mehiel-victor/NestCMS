<script setup lang="ts">
import { CButton } from '@chakra-ui/c-button'
import { CInput } from '@chakra-ui/c-input'
import { LockKeyhole, Mail } from '@lucide/vue'

definePageMeta({
  requiresAuth: false
})

const api = useNestApi()
const session = useAuthSession()
const route = useRoute()
const email = ref('')
const password = ref('')
const loggingIn = ref(false)
const error = ref('')

const safePath = (path: unknown) => {
  if (typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//')) {
    return '/'
  }

  return path
}

const requestedNextPath = computed(() => {
  return safePath(route.query.next)
})

const reason = computed(() => {
  const raw = route.query.reason
  if (raw === 'forbidden') {
    return 'Este perfil demo nao tem acesso a esse caminho.'
  }

  return ''
})

const login = async () => {
  loggingIn.value = true
  error.value = ''

  try {
    await api.login(email.value, password.value)
    await navigateTo(requestedNextPath.value)
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Nao foi possivel entrar.'
  } finally {
    loggingIn.value = false
  }
}

onMounted(() => {
  session.hydrate()

  if (session.isAuthenticated.value && !reason.value) {
    void navigateTo(requestedNextPath.value)
  }
})
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="auth-eyebrow">Sessao demo</p>
      <h1 class="auth-title">Acessar NestCMS</h1>
      <p class="auth-subtitle">
        Entre com um perfil seedado. A autenticacao e local e existe apenas para demonstrar papeis de acesso.
      </p>

      <p v-if="reason" class="notice error">{{ reason }}</p>
      <p v-if="error" class="notice error">{{ error }}</p>

      <form class="auth-form" @submit.prevent="login">
        <label for="email">E-mail</label>
        <div class="auth-input-row">
          <Mail :size="18" aria-hidden="true" />
          <CInput
            id="email"
            v-model="email"
            type="email"
            placeholder="admin@nestcms.test"
            autocomplete="username"
            required
          />
        </div>

        <label for="password">Senha</label>
        <div class="auth-input-row">
          <LockKeyhole :size="18" aria-hidden="true" />
          <CInput
            id="password"
            v-model="password"
            type="password"
            placeholder="Senha"
            autocomplete="current-password"
            required
          />
        </div>

        <CButton type="submit" color-scheme="green" size="lg" :is-loading="loggingIn">
          Entrar na demo
        </CButton>

        <p class="auth-footnote">
          Perfis disponiveis: admin@nestcms.test, operator@nestcms.test e finance@nestcms.test.
        </p>
      </form>
    </section>
  </main>
</template>
