import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppAvatar from '@/components/global/AppAvatar.vue'

describe('AppAvatar', () => {
  it('рисует инициалы, если нет фото', () => {
    const wrapper = mount(AppAvatar, {
      props: { name: 'Иван Петров' },
    })

    expect(wrapper.get('.app-avatar__initials').text()).toBe('ИП')
    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.classes()).not.toContain('app-avatar--image')
    expect(wrapper.attributes('aria-hidden')).toBe('true')
  })

  it('показывает изображение и подпись', () => {
    const wrapper = mount(AppAvatar, {
      props: {
        name: 'Иван',
        src: 'https://example.com/a.jpg',
        label: 'Иван',
        size: 48,
      },
    })

    expect(wrapper.get('img').attributes('src')).toBe('https://example.com/a.jpg')
    expect(wrapper.classes()).toContain('app-avatar--image')
    expect(wrapper.attributes('role')).toBe('img')
    expect(wrapper.attributes('aria-label')).toBe('Иван')
    expect(wrapper.attributes('style')).toContain('48px')
  })
})
