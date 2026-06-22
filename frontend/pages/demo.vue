<script setup lang="ts">
import { ArrowRight, CheckCircle2, Eye, Package, Play, RotateCcw, ShieldCheck, ShoppingCart } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'

definePageMeta({
  requiresAuth: false
})

const api = useNestApi()
const session = useAuthSession()
session.hydrate()

const isSubmitting = ref('')
const isResetting = ref(false)
const error = ref('')
const success = ref('')

type DemoCredential = {
  id: 'admin' | 'operator' | 'finance'
  label: string
  email: string
  password: string
  description: string
}

const credentials: DemoCredential[] = [
  { id: 'admin', label: 'Admin', email: 'admin@nestcms.test', password: 'Admin@123', description: 'Visão geral e controle de catalogo/pedidos' },
  { id: 'operator', label: 'Operador', email: 'operator@nestcms.test', password: 'Operator@123', description: 'Operações, pedidos e dashboard operacional' },
  { id: 'finance', label: 'Financeiro', email: 'finance@nestcms.test', password: 'Finance@123', description: 'Faturamento, risco e conciliação simples' }
]

const quickLogin = async (profile: DemoCredential) => {
  isSubmitting.value = profile.id
  error.value = ''
  success.value = ''

  try {
    await api.login(profile.email, profile.password)
    success.value = `Sessao iniciada como ${profile.label}. Redirecionando para o painel...`
    await navigateTo('/')
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Nao foi possivel iniciar no modo demo.'
  } finally {
    isSubmitting.value = ''
  }
}

const resetDemo = async () => {
  isResetting.value = true
  error.value = ''
  success.value = ''

  try {
    await api.resetDemo()
    success.value = 'Estado demo restaurado com produtos, pedidos e carrinhos seedados.'
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Nao foi possivel restaurar o estado demo.'
  } finally {
    isResetting.value = false
  }
}

const isLoggedIn = computed(() => !!session.accessToken.value)
const userEmail = computed(() => session.user.value?.email ?? '')
</script>

<template>
  <main class="demo-page">
    <section class="panel">
      <div class="demo-hero">
        <p class="eyebrow">Portfolio demo frontend-only</p>
        <h1 class="page-title">NestCMS em sessao demo local</h1>
        <p class="page-subtitle">
          Explore dashboard, catalogo, checkout, pedidos, estoque e recuperacao com dados mock persistidos no navegador.
          Nenhum pagamento, e-mail, envio, nota fiscal ou integracao externa e criado.
        </p>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Acesso rapido ao painel</h2>
          <p class="panel-kicker">Clique em qualquer perfil para entrar na sessao demo em segundos.</p>
        </div>
        <Eye :size="20" aria-hidden="true" />
      </div>

      <div v-if="isLoggedIn" class="notice">
        Ja autenticado como {{ userEmail }}. <NuxtLink to="/">Ir para o dashboard</NuxtLink>.
      </div>

      <div v-if="error" class="notice error">{{ error }}</div>
      <div v-if="success" class="notice success">{{ success }}</div>

      <div class="demo-grid">
        <div v-for="profile in credentials" :key="profile.id" class="panel quick-card">
          <h3 class="quick-card-title">{{ profile.label }}</h3>
          <p class="quick-card-description">{{ profile.description }}</p>
          <p class="quick-card-meta"><span>E-mail:</span> {{ profile.email }}</p>
          <div class="split-actions full">
            <small>Senha: {{ profile.password }}</small>
            <CButton
              color-scheme="green"
              size="sm"
              :is-loading="isSubmitting === profile.id"
              @click="quickLogin(profile)"
            >
              <span class="icon-label">
                <Play :size="14" aria-hidden="true" />
                Entrar na demo
              </span>
            </CButton>
          </div>
        </div>
      </div>

      <div class="split-actions full">
        <small>Produtos, pedidos e recuperacoes simuladas ficam salvos neste navegador ate o reset.</small>
        <CButton color-scheme="orange" size="sm" :is-loading="isResetting" @click="resetDemo">
          <span class="icon-label">
            <RotateCcw :size="14" aria-hidden="true" />
            Resetar demo
          </span>
        </CButton>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Roteiro recomendado</h2>
          <p class="panel-kicker">Caminho curto para avaliar os fluxos mock principais.</p>
        </div>
        <CheckCircle2 :size="20" aria-hidden="true" />
      </div>

      <div class="demo-checklist">
        <NuxtLink to="/checkout" class="demo-link">
          <ShoppingCart :size="16" aria-hidden="true" />
          <div>
            <strong>Checkout como visitante</strong>
            <p>Simule checkout com PIX, cartao ou boleto sem coletar dados sensiveis.</p>
          </div>
        </NuxtLink>
        <NuxtLink to="/catalog" class="demo-link">
          <Package :size="16" aria-hidden="true" />
          <div>
            <strong>Gestao de catalogo</strong>
            <p>Cadastre SKU, estoque, margem e visibilidade na sessao local.</p>
          </div>
        </NuxtLink>
        <NuxtLink to="/" class="demo-link">
          <ShieldCheck :size="16" aria-hidden="true" />
          <div>
            <strong>Dashboard executivo</strong>
            <p>Veja KPIs recalculados a partir dos pedidos e carrinhos mock.</p>
          </div>
        </NuxtLink>
      </div>

      <NuxtLink to="/login" class="demo-cta">
        <span>Ver fluxo de login manual</span>
        <ArrowRight :size="16" aria-hidden="true" />
      </NuxtLink>
    </section>
  </main>
</template>
