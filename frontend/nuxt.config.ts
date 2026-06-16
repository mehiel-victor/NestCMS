export default defineNuxtConfig({
  ssr: false,
  devtools: { enabled: true },
  experimental: {
    appManifest: false
  },
  css: ['~/assets/scss/main.scss'],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080'
    }
  },
  app: {
    head: {
      title: 'NestCMS',
      meta: [
        {
          name: 'description',
          content: 'Commerce CMS MVP for DTC brands'
        }
      ]
    }
  },
  vite: {
    server: {
      allowedHosts: true
    }
  }
})
