import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'

const laravelTarget = process.env.VITE_API_PROXY || 'http://localhost:8080'

function laravelProxy(options: { spaGet?: boolean } = {}) {
  return {
    target: laravelTarget,
    changeOrigin: true,
    bypass(req: { method?: string }) {
      if (options.spaGet && (req.method === 'GET' || req.method === 'HEAD')) {
        return '/index.html'
      }
    },
  }
}

export default defineConfig({
  plugins: [
    vue(),
    vuetify({ autoImport: true }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/api': laravelProxy(),
      '/up': laravelProxy(),
      '/sanctum': laravelProxy(),
      '/user': laravelProxy(),
      '/logout': laravelProxy(),
      '/login': laravelProxy({ spaGet: true }),
    },
  },
})
