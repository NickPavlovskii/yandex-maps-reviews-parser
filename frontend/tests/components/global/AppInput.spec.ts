import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppInput from '@/components/global/AppInput.vue'

describe('AppInput', () => {
  it('пишет метку и обновляет модель', async () => {
    const wrapper = mount(AppInput, {
      props: {
        label: 'Почта',
        modelValue: '',
        placeholder: 'you@mail.test',
      },
    })

    expect(wrapper.get('label').text()).toBe('Почта')
    await wrapper.get('input').setValue('admin@example.com')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['admin@example.com'])
  })

  it('показывает и скрывает пароль', async () => {
    const wrapper = mount(AppInput, {
      props: {
        type: 'password',
        modelValue: 'secret',
      },
    })

    const input = wrapper.get('input')
    expect(input.attributes('type')).toBe('password')
    expect(wrapper.get('.app-input__reveal').attributes('aria-label')).toBe('Показать пароль')

    await wrapper.get('.app-input__reveal').trigger('click')

    expect(wrapper.get('input').attributes('type')).toBe('text')
    expect(wrapper.get('.app-input__reveal').attributes('aria-pressed')).toBe('true')
  })

  it('очищает значение по кнопке', async () => {
    const wrapper = mount(AppInput, {
      props: {
        clearable: true,
        modelValue: 'https://yandex.ru/maps/org/1',
      },
    })

    await wrapper.get('.app-input__clear').trigger('click')

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([''])
  })

  it('не рисует кнопку глаза у обычного поля', () => {
    const wrapper = mount(AppInput, {
      props: { type: 'text', modelValue: '' },
    })

    expect(wrapper.find('.app-input__reveal').exists()).toBe(false)
  })
})
