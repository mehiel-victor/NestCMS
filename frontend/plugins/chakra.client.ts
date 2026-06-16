import ChakraUIVuePlugin, { extendChakra } from '@chakra-ui/vue-next'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(
    ChakraUIVuePlugin,
    extendChakra({
      cssReset: false
    })
  )
})

