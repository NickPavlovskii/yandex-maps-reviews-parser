import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ReviewsPager from '@/components/reviews/ReviewsPager.vue'
import type { ReviewsMeta } from '@/types/api'

function makeMeta(overrides: Partial<ReviewsMeta> = {}): ReviewsMeta {
  return {
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 12,
    ...overrides,
  }
}

describe('ReviewsPager', () => {
  it('прячет пагинацию без отзывов', () => {
    const empty = mount(ReviewsPager, { props: { meta: null } })
    const zero = mount(ReviewsPager, {
      props: { meta: makeMeta({ total: 0 }) },
    })

    expect(empty.find('.pager').exists()).toBe(false)
    expect(zero.find('.pager').exists()).toBe(false)
  })

  it('пишет диапазон и не рисует страницы, если лист один', () => {
    const wrapper = mount(ReviewsPager, {
      props: { meta: makeMeta({ total: 12, per_page: 50, last_page: 1 }) },
    })

    expect(wrapper.get('.pager__summary').text()).toContain('1–12 из')
    expect(wrapper.find('.pager__pages').exists()).toBe(false)
  })

  it('листает страницы и ставит многоточие', async () => {
    const wrapper = mount(ReviewsPager, {
      props: {
        meta: makeMeta({
          current_page: 6,
          last_page: 12,
          per_page: 50,
          total: 600,
        }),
      },
    })

    expect(wrapper.get('.pager__summary').text()).toContain('251–300 из')
    expect(wrapper.findAll('.pager__ellipsis')).toHaveLength(2)
    expect(wrapper.get('.pager__page--active').text()).toBe('6')

    await wrapper.get('[aria-label="Следующая страница"]').trigger('click')
    expect(wrapper.emitted('change')?.[0]).toEqual([7])
  })

  it('блокирует стрелки на краях и во время загрузки', () => {
    const first = mount(ReviewsPager, {
      props: {
        busy: true,
        meta: makeMeta({ current_page: 1, last_page: 4, total: 200 }),
      },
    })

    expect(first.get('[aria-label="Предыдущая страница"]').attributes('disabled')).toBeDefined()
    expect(first.get('.pager__page').attributes('disabled')).toBeDefined()
  })
})
