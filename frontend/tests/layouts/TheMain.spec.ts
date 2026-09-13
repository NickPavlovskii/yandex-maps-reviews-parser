import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import TheMain from '@/layouts/TheMain.vue'
import { authStore } from '@/store/auth'

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'settings', component: { template: '<div>settings</div>' } },
      { path: '/reviews', name: 'organization', component: { template: '<div>org</div>' } },
      { path: '/login', name: 'login', component: { template: '<div>login</div>' } },
    ],
  })
}

describe('TheMain', () => {
  beforeEach(() => {
    authStore.user = { id: 1, name: 'Админ', email: 'admin@example.com' }
    document.body.style.overflow = ''
  })

  it('открывает и закрывает мобильное меню', async () => {
    const router = makeRouter()
    await router.push('/')

    const wrapper = mount(TheMain, {
      global: { plugins: [router] },
    })

    const toggle = wrapper.get('.menu-toggle')
    expect(toggle.attributes('aria-expanded')).toBe('false')
    expect(wrapper.get('#mobile-nav').classes()).not.toContain('nav--mobile-open')

    await toggle.trigger('click')

    expect(toggle.attributes('aria-expanded')).toBe('true')
    expect(wrapper.get('#mobile-nav').classes()).toContain('nav--mobile-open')
    expect(document.body.style.overflow).toBe('hidden')

    await wrapper.get('.menu-backdrop').trigger('click')

    expect(toggle.attributes('aria-expanded')).toBe('false')
    expect(wrapper.get('#mobile-nav').classes()).not.toContain('nav--mobile-open')
  })

  it('закрывает меню при переходе по ссылке', async () => {
    const router = makeRouter()
    await router.push('/')

    const wrapper = mount(TheMain, {
      global: { plugins: [router] },
    })

    await wrapper.get('.menu-toggle').trigger('click')
    await wrapper.get('#mobile-nav a[href="/reviews"]').trigger('click')
    await router.isReady()

    expect(wrapper.get('#mobile-nav').classes()).not.toContain('nav--mobile-open')
  })

  it('выходит из кабинета из меню', async () => {
    const router = makeRouter()
    await router.push('/')
    vi.spyOn(authStore, 'logout').mockResolvedValue()

    const wrapper = mount(TheMain, {
      global: { plugins: [router] },
    })

    const buttons = wrapper.findAll('.nav--mobile .app-button')
    await buttons[0].trigger('click')
    await vi.waitFor(() => {
      expect(authStore.logout).toHaveBeenCalled()
      expect(router.currentRoute.value.name).toBe('login')
    })
  })
})
