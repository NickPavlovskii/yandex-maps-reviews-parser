import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/api/auth', () => ({
  getUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}))

import { getUser, login, logout } from '@/api/auth'
import { authStore } from '@/store/auth'

const user = { id: 1, name: 'Админ', email: 'admin@example.com' }

describe('authStore', () => {
  beforeEach(() => {
    authStore.user = null
    authStore.loaded = false
    vi.mocked(getUser).mockReset()
    vi.mocked(login).mockReset()
    vi.mocked(logout).mockReset()
  })

  it('подгружает пользователя один раз', async () => {
    vi.mocked(getUser).mockResolvedValue(user)

    await authStore.ensureLoaded()
    await authStore.ensureLoaded()

    expect(authStore.user).toEqual(user)
    expect(authStore.loaded).toBe(true)
    expect(getUser).toHaveBeenCalledTimes(1)
  })

  it('считает сессию пустой, если запрос пользователя упал', async () => {
    vi.mocked(getUser).mockRejectedValue(new Error('401'))

    await authStore.ensureLoaded()

    expect(authStore.user).toBeNull()
    expect(authStore.loaded).toBe(true)
  })

  it('логинит и выходит', async () => {
    vi.mocked(login).mockResolvedValue(user)
    vi.mocked(logout).mockResolvedValue(undefined)

    await authStore.login('admin@example.com', 'password', true)
    expect(login).toHaveBeenCalledWith('admin@example.com', 'password', true)
    expect(authStore.user).toEqual(user)

    await authStore.logout()
    expect(logout).toHaveBeenCalledTimes(1)
    expect(authStore.user).toBeNull()
    expect(authStore.loaded).toBe(true)
  })
})
