import axios, { type AxiosInstance } from 'axios'

const jsonHeaders = {
  Accept: 'application/json',
}

export const http = axios.create({
  headers: jsonHeaders,
  withCredentials: true,
  withXSRFToken: true,
})

export const apiClient = axios.create({
  baseURL: '/api',
  headers: jsonHeaders,
  withCredentials: true,
  withXSRFToken: true,
})

function attachUnauthorizedRedirect(client: AxiosInstance) {
  client.interceptors.response.use(
    (response) => response,
    async (error) => {
      const url = String(error.config?.url ?? '')
      const isAuthProbe = url.includes('/user') || url.includes('/login')

      if (error.response?.status === 401 && !isAuthProbe) {
        const { authStore } = await import('@/store/auth')
        authStore.clear()

        const router = (await import('@/router')).default
        if (router.currentRoute.value.name !== 'login') {
          await router.push({ name: 'login' })
        }
      }

      return Promise.reject(error)
    },
  )
}

attachUnauthorizedRedirect(http)
attachUnauthorizedRedirect(apiClient)
