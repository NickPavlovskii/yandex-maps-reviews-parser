import { config } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import AppAvatar from '@/components/global/AppAvatar.vue'
import AppButton from '@/components/global/AppButton.vue'
import AppInfoCard from '@/components/global/AppInfoCard.vue'
import AppInput from '@/components/global/AppInput.vue'
import AppPageHeader from '@/components/global/AppPageHeader.vue'
import AppSelect from '@/components/global/AppSelect.vue'
import AppSpinner from '@/components/global/AppSpinner.vue'

const vuetify = createVuetify({ components, directives })

config.global.plugins = [vuetify]
config.global.components = {
  AppAvatar,
  AppButton,
  AppInfoCard,
  AppInput,
  AppPageHeader,
  AppSelect,
  AppSpinner,
}

class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}

Object.defineProperty(globalThis, 'ResizeObserver', {
  writable: true,
  value: ResizeObserverStub,
})

Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener() {},
    removeListener() {},
    addEventListener() {},
    removeEventListener() {},
    dispatchEvent() {
      return false
    },
  }),
})
