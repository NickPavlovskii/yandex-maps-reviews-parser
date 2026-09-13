import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/api/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

import { apiClient } from '@/api/axios'
import {
  REVIEWS_PER_PAGE,
  createOrganization,
  getOrganization,
  getOrganizationReviews,
} from '@/api/organizations'

describe('organizations api', () => {
  beforeEach(() => {
    vi.mocked(apiClient.get).mockReset()
    vi.mocked(apiClient.post).mockReset()
  })

  it('создаёт организацию и достаёт data', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({
      data: { data: { id: 7, url: 'https://yandex.ru/maps/org/7' } },
    })

    const organization = await createOrganization('https://yandex.ru/maps/org/7')

    expect(apiClient.post).toHaveBeenCalledWith('/organizations', {
      url: 'https://yandex.ru/maps/org/7',
    })
    expect(organization.id).toBe(7)
  })

  it('запрашивает организацию по id', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: { id: 8 } },
    })

    await expect(getOrganization(8)).resolves.toEqual({ id: 8 })
    expect(apiClient.get).toHaveBeenCalledWith('/organizations/8')
  })

  it('передаёт фильтры отзывов и отбрасывает пустые', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: [], meta: { current_page: 2, last_page: 3, per_page: 50, total: 101 } },
    })

    await getOrganizationReviews(3, {
      page: 2,
      rating: 0,
      q: '',
      sort: 'oldest',
    })

    expect(apiClient.get).toHaveBeenCalledWith('/organizations/3/reviews', {
      params: {
        page: 2,
        per_page: REVIEWS_PER_PAGE,
        rating: undefined,
        q: undefined,
        sort: 'oldest',
      },
    })
  })
})
