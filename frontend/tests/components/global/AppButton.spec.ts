import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppButton from '@/components/global/AppButton.vue'

describe('AppButton', () => {
  it('показывает заголовок и шлёт click', async () => {
    const wrapper = mount(AppButton, {
      props: { title: 'Сохранить' },
    })

    expect(wrapper.text()).toContain('Сохранить')
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('включает обводку и блокировку', () => {
    const wrapper = mount(AppButton, {
      props: {
        title: 'Отмена',
        border: true,
        disabled: true,
      },
    })

    expect(wrapper.classes()).toContain('app-button--border')
    expect(wrapper.get('button').attributes('disabled')).toBeDefined()
  })
})
