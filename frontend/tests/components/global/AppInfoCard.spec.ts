import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppInfoCard from '@/components/global/AppInfoCard.vue'

describe('AppInfoCard', () => {
  it('выводит заголовок, значение и подпись', () => {
    const wrapper = mount(AppInfoCard, {
      props: {
        title: 'Отзывы',
        value: 128,
        subtitle: 'за всё время',
        icon: 'mdi-star',
      },
    })

    expect(wrapper.get('.app-info-card__title').text()).toBe('Отзывы')
    expect(wrapper.get('.app-info-card__value').text()).toBe('128')
    expect(wrapper.get('.app-info-card__subtitle').text()).toBe('за всё время')
    expect(wrapper.find('.app-info-card__icon').exists()).toBe(true)
  })
})
