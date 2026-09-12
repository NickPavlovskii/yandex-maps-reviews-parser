import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { apiPlugin } from './api'
import GlobalComponents from './components/global'
import './scss/main.scss'

const app = createApp(App)

app.use(router)
app.use(vuetify)
app.use(apiPlugin)
app.use(GlobalComponents)
app.mount('#app')
