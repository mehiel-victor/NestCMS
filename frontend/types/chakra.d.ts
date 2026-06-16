declare module '@chakra-ui/vue-next' {
  import type { Plugin } from 'vue'

  const ChakraUIVuePlugin: Plugin

  export default ChakraUIVuePlugin
  export function extendChakra(options?: Record<string, unknown>): Record<string, unknown>
}

declare module '@chakra-ui/c-button' {
  import type { DefineComponent } from 'vue'

  export const CButton: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
}

declare module '@chakra-ui/c-input' {
  import type { DefineComponent } from 'vue'

  export const CInput: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
}

