export default defineNuxtConfig({
  ssr: false,
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },
  css: ['~/assets/scss/main.scss'],
  app: {
    head: {
      title: 'NestCMS Portfolio Demo',
      meta: [
        {
          name: 'description',
          content: 'Frontend-only commerce operations portfolio demo with local mock state.'
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
