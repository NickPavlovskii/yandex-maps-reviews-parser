import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import StarRating from '@/components/reviews/StarRating.vue'
import { MAX_RATING } from '@/constants/rating'

describe('StarRating', () => {
  it('рисует пять звёзд и подпись рейтинга', () => {
    const wrapper = mount(StarRating, {
      props: { value: 3.8, size: 'md' },
    })

    expect(wrapper.findAll('.stars__icon')).toHaveLength(MAX_RATING)
    expect(wrapper.attributes('aria-label')).toBe('3 из 5')
    expect(wrapper.classes()).toContain('stars--md')
  })

  it('не уходит за пределы шкалы', () => {
    const empty = mount(StarRating, { props: { value: null } })
    const overflow = mount(StarRating, { props: { value: 12 } })

    expect(empty.attributes('aria-label')).toBe('0 из 5')
    expect(overflow.attributes('aria-label')).toBe('5 из 5')
  })
})
