import type { App } from 'vue'
import { apiClient } from './axios'
import { getHealth } from './health'

export const api = {
  axios: apiClient,
  health: {
    get: getHealth,
  },
}

export const apiPlugin = {
  install(app: App) {
    app.config.globalProperties.$api = api
    app.provide('api', api)
  },
}

export type Api = typeof api
