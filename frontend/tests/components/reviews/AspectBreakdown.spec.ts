import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AspectBreakdown from '@/components/reviews/AspectBreakdown.vue'

describe('AspectBreakdown', () => {
  it('не рисуется без аспектов', () => {
    const wrapper = mount(AspectBreakdown, {
      props: { aspects: [] },
    })

    expect(wrapper.find('.aspects').exists()).toBe(false)
  })

  it('ставит 👎 на тему с самой большой долей негатива', () => {
    const wrapper = mount(AspectBreakdown, {
      props: {
        aspects: [
          { text: 'Еда', count: 1389, positive: 1080, negative: 263 },
          { text: 'Цены', count: 287, positive: 140, negative: 140 },
        ],
      },
    })

    expect(wrapper.text()).toContain('Цены')
    expect(wrapper.text()).toContain('Чаще всего жалуются на тему «Цены»')
    expect(wrapper.get('strong').text()).toContain('Еда')
  })
})
