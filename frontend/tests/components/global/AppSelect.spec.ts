import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import AppSelect from '@/components/global/AppSelect.vue'

const options = [
  { value: 'newest', label: 'Сначала новые' },
  { value: 'oldest', label: 'Сначала старые' },
]

describe('AppSelect', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('показывает плейсхолдер и выбирает значение', async () => {
    const wrapper = mount(AppSelect, {
      props: {
        options,
        placeholder: 'Сортировка',
      },
      attachTo: document.body,
    })

    expect(wrapper.get('.app-select__control').text()).toContain('Сортировка')

    await wrapper.get('.app-select__control').trigger('click')
    await wrapper.get('.app-select__option').trigger('click')

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['newest'])
    expect(wrapper.find('.app-select__list').exists()).toBe(false)
  })

  it('не открывается в disabled', async () => {
    const wrapper = mount(AppSelect, {
      props: {
        options,
        disabled: true,
      },
    })

    await wrapper.get('.app-select__control').trigger('click')

    expect(wrapper.find('.app-select__list').exists()).toBe(false)
    expect(wrapper.get('.app-select__control').attributes('disabled')).toBeDefined()
  })
})
