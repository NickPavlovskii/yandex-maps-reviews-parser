import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppPageHeader from '@/components/global/AppPageHeader.vue'

describe('AppPageHeader', () => {
  it('рисует шапку и правый слот', () => {
    const wrapper = mount(AppPageHeader, {
      props: {
        title: 'Настройки',
        subtitle: 'Карточка организации',
        icon: 'mdi-cog',
      },
      slots: {
        right: '<button type="button">Выйти</button>',
        default: '<p>Дополнительно</p>',
      },
    })

    expect(wrapper.get('.app-page-header__title').text()).toBe('Настройки')
    expect(wrapper.get('.app-page-header__subtitle').text()).toBe('Карточка организации')
    expect(wrapper.get('.app-page-header__right').text()).toContain('Выйти')
    expect(wrapper.get('.app-page-header__extra').text()).toContain('Дополнительно')
  })
})
