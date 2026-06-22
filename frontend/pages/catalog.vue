<script setup lang="ts">
import { Plus, Save } from '@lucide/vue'
import { CButton } from '@chakra-ui/c-button'
import { CInput } from '@chakra-ui/c-input'
import type { Product } from '~/types'

definePageMeta({
  requiresAuth: true,
  allowedRoles: ['admin', 'operator', 'finance']
})

const api = useNestApi()
const products = ref<Product[]>([])
const loading = ref(true)
const saving = ref(false)
const message = ref('')
const error = ref('')

const form = reactive({
  title: 'Novo Bundle DTC',
  description: 'Produto criado na sessao demo do NestCMS.',
  product_type: 'physical',
  visibility: 'published',
  price: 149.9,
  compare_at_price: 189.9,
  margin_percent: 52,
  category_id: 1,
  collection_id: 1,
  sku: 'SKU-NOVO-001',
  option_name: 'Tamanho',
  option_value: 'Unico',
  stock: 20,
  low_stock_threshold: 6
})

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    products.value = await api.products()
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Falha ao carregar catalogo.'
  } finally {
    loading.value = false
  }
}

const createProduct = async () => {
  saving.value = true
  message.value = ''
  error.value = ''

  try {
    await api.createProduct({
      title: form.title,
      description: form.description,
      product_type: form.product_type,
      visibility: form.visibility,
      price: Number(form.price),
      compare_at_price: Number(form.compare_at_price),
      margin_percent: Number(form.margin_percent),
      category_id: Number(form.category_id),
      collection_id: Number(form.collection_id),
      custom_fields: {
        canal: 'DTC',
        setup: 'MVP'
      },
      media: [
        {
          media_type: 'image',
          url: 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796',
          title: form.title
        }
      ],
      variants: [
        {
          sku: form.sku,
          option_name: form.option_name,
          option_value: form.option_value,
          price: Number(form.price),
          stock: Number(form.stock),
          low_stock_threshold: Number(form.low_stock_threshold)
        }
      ]
    })

    message.value = 'Produto criado na sessao demo e catalogo local atualizado.'
    await load()
  } catch (exception) {
    error.value = exception instanceof Error ? exception.message : 'Nao foi possivel criar o produto.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppShell>
    <div class="topbar">
      <div>
        <p class="eyebrow">Catalogo demo</p>
        <h1 class="page-title">Produtos e variantes</h1>
        <p class="page-subtitle">
          Cadastro local com SKU, preco, visibilidade, margem, midia e estoque inicial. Rascunhos e agendados nao entram no checkout publico.
        </p>
      </div>
    </div>

    <div class="grid two">
      <section class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Novo produto demo</h2>
            <p class="panel-kicker">Cria uma oferta apenas no estado local do navegador.</p>
          </div>
          <Plus :size="20" aria-hidden="true" />
        </div>

        <form class="form-grid" @submit.prevent="createProduct">
          <div class="field full">
            <label for="title">Titulo</label>
            <CInput id="title" v-model="form.title" />
          </div>

          <div class="field full">
            <label for="description">Descricao</label>
            <textarea id="description" v-model="form.description" />
          </div>

          <div class="field">
            <label for="type">Tipo</label>
            <select id="type" v-model="form.product_type">
              <option value="physical">Fisico</option>
              <option value="digital">Digital</option>
              <option value="bundle">Bundle</option>
            </select>
          </div>

          <div class="field">
            <label for="visibility">Visibilidade</label>
            <select id="visibility" v-model="form.visibility">
              <option value="draft">Rascunho</option>
              <option value="published">Publicado</option>
              <option value="scheduled">Agendado</option>
            </select>
          </div>

          <div class="field">
            <label for="price">Preco</label>
            <input id="price" v-model.number="form.price" type="number" min="0" step="0.01" />
          </div>

          <div class="field">
            <label for="margin">Margem</label>
            <input id="margin" v-model.number="form.margin_percent" type="number" min="0" step="0.1" />
          </div>

          <div class="field">
            <label for="sku">SKU</label>
            <input id="sku" v-model="form.sku" />
          </div>

          <div class="field">
            <label for="variant">Variante</label>
            <input id="variant" v-model="form.option_value" />
          </div>

          <div class="field">
            <label for="stock">Estoque</label>
            <input id="stock" v-model.number="form.stock" type="number" min="0" />
          </div>

          <div class="field">
            <label for="threshold">Alerta baixo</label>
            <input id="threshold" v-model.number="form.low_stock_threshold" type="number" min="0" />
          </div>

          <div class="split-actions full">
            <span v-if="message" class="notice">{{ message }}</span>
            <span v-if="error" class="notice error">{{ error }}</span>
            <CButton color-scheme="green" type="submit" :is-loading="saving">
              <span class="icon-label">
                <Save :size="16" aria-hidden="true" />
                Criar demo
              </span>
            </CButton>
          </div>
        </form>
      </section>

      <ProductTable :products="products" />
    </div>

    <div v-if="loading" class="notice" style="margin-top: 18px">Carregando catalogo...</div>
  </AppShell>
</template>
