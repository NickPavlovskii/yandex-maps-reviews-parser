import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppSpinner from '@/components/global/AppSpinner.vue'

describe('AppSpinner', () => {
  it('рисует кружок без текста', () => {
    const wrapper = mount(AppSpinner)

    expect(wrapper.find('.app-spinner__circle').exists()).toBe(true)
    expect(wrapper.find('.app-spinner__label').exists()).toBe(false)
  })

  it('берёт подпись из пропа или слота', () => {
    const withLabel = mount(AppSpinner, {
      props: { label: 'Собираем отзывы…' },
    })
    const withSlot = mount(AppSpinner, {
      slots: { default: 'Ещё секунда' },
    })

    expect(withLabel.get('.app-spinner__label').text()).toBe('Собираем отзывы…')
    expect(withSlot.get('.app-spinner__label').text()).toBe('Ещё секунда')
  })
})
