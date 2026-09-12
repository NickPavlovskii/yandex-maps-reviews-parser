import type { App, Plugin } from 'vue'
import { globalComponents } from './manifest'

const plugin: Plugin = {
  install(app: App) {
    globalComponents.forEach(({ tag, component }) => {
      app.component(tag, component)
    })
  },
}

export default plugin
export { globalComponents } from './manifest'
