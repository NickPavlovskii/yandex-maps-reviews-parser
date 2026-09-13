import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ReviewCard from '@/components/reviews/ReviewCard.vue'
import type { Review } from '@/types/api'

function makeReview(overrides: Partial<Review> = {}): Review {
  return {
    id: 1,
    yandex_review_id: '1',
    author: 'Иван Петров',
    rating: 5,
    text: 'Отличное место',
    business_reply: null,
    published_at: '2024-03-15T10:00:00.000Z',
    ...overrides,
  }
}

describe('ReviewCard', () => {
  it('показывает автора, текст и дату', () => {
    const wrapper = mount(ReviewCard, {
      props: { review: makeReview() },
    })

    expect(wrapper.get('strong').text()).toBe('Иван Петров')
    expect(wrapper.get('.review__text').text()).toBe('Отличное место')
    expect(wrapper.get('time').text()).toBe('15 марта 2024')
    expect(wrapper.find('.reply').exists()).toBe(false)
  })

  it('подставляет заглушки и ответ организации', () => {
    const wrapper = mount(ReviewCard, {
      props: {
        review: makeReview({
          author: null,
          text: null,
          business_reply: 'Спасибо за отзыв',
        }),
      },
    })

    expect(wrapper.get('strong').text()).toBe('Без имени')
    expect(wrapper.get('.review__text').text()).toBe('Без текста')
    expect(wrapper.get('.reply').text()).toContain('Ответ организации')
    expect(wrapper.get('.reply').text()).toContain('Спасибо за отзыв')
  })
})
