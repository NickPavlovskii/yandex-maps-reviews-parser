import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import { ru } from 'vuetify/locale'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

export default createVuetify({
  locale: {
    locale: 'ru',
    fallback: 'en',
    messages: { ru },
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1e3a8a',
          secondary: '#64748b',
        },
      },
    },
  },
})
