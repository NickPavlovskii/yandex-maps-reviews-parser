import { reactive } from 'vue'
import { getUser, login as loginRequest, logout as logoutRequest } from '@/api/auth'
import type { User } from '@/types/api'

export const authStore = reactive({
  user: null as User | null,
  loaded: false,

  clear() {
    this.user = null
    this.loaded = true
  },

  async ensureLoaded() {
    if (this.loaded) {
      return
    }

    try {
      this.user = await getUser()
    } catch {
      this.user = null
    } finally {
      this.loaded = true
    }
  },

  async login(email: string, password: string, remember = false) {
    this.user = await loginRequest(email, password, remember)
    this.loaded = true
  },

  async logout() {
    await logoutRequest()
    this.clear()
  },
})
