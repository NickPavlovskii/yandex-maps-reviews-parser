import { beforeEach, describe, expect, it } from 'vitest'
import router from '@/router'
import { authStore } from '@/store/auth'

describe('router guards', () => {
  beforeEach(() => {
    authStore.user = null
    authStore.loaded = true
  })

  it('отправляет гостя с закрытых страниц на вход', async () => {
    await router.push('/reviews')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/reviews')
  })

  it('уводит авторизованного с логина в настройки', async () => {
    authStore.user = { id: 1, name: 'Админ', email: 'admin@example.com' }

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('settings')
  })

  it('оставляет 404 открытым без авторизации', async () => {
    await router.push('/unknown-page')

    expect(router.currentRoute.value.name).toBe('notFound')
  })
})
