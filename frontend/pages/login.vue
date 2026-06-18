<script setup lang="ts">
import { CButton } from '@chakra-ui/c-button'
import { CInput } from '@chakra-ui/c-input'
import { Mail } from '@lucide/vue'

definePageMeta({
  requiresAuth: false
})

const api = useNestApi()
const route = useRoute()
const email = ref('')
const sending = ref(false)
const success = ref(false)
const error = ref('')

const requestedNextPath = computed(() => {
  const raw = route.query.next
  return typeof raw === 'string' ? raw : '/'
})

const reason = computed(() => {
  const raw = route.query.reason
  if (raw === 'forbidden') {
    return 'Seu usuário não tem acesso a esse caminho.'
  }

  return ''
})

const requestLink = async () => {
  sending.value = true
  error.value = ''

  try {
    if (requestedNextPath.value.startsWith('/')) {
      localStorage.setItem('nestcms_auth_next', requestedNextPath.value)
    }

    await api.requestMagicLink(email.value)
    success.value = true
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Não foi possível solicitar o acesso.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="auth-eyebrow">Painel seguro por link</p>
      <h1 class="auth-title">Acessar Painel NestCMS</h1>
      <p class="auth-subtitle">
        Use o e-mail do convite para receber o link de acesso sem senha.
      </p>

      <p v-if="reason" class="notice error">{{ reason }}</p>
      <p v-if="error" class="notice error">{{ error }}</p>
      <p v-if="success" class="notice">Enviamos o link para o e-mail informado, se ele estiver cadastrado.</p>

      <form v-if="!success" class="auth-form" @submit.prevent="requestLink">
        <label for="email">E-mail</label>
        <div class="auth-input-row">
          <Mail :size="18" aria-hidden="true" />
          <CInput id="email" v-model="email" type="email" placeholder="seu@exemplo.com" />
        </div>

        <CButton type="submit" color-scheme="green" size="lg" :is-loading="sending">
          Enviar link de acesso
        </CButton>

        <p class="auth-footnote">
          Acesse o painel como admin, operador ou financeiro. O cadastro é feito apenas por convite.
        </p>
      </form>

      <CButton v-else variant="outline" @click="success = false; email = ''">
        Enviar novamente
      </CButton>
    </section>
  </main>
</template>
