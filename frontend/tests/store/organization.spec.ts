import { AxiosError } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Organization, ReviewsResponse } from '@/types/api'

vi.mock('@/api/organizations', () => ({
  createOrganization: vi.fn(),
  getOrganization: vi.fn(),
  getOrganizationReviews: vi.fn(),
}))

import { createOrganization, getOrganization, getOrganizationReviews } from '@/api/organizations'
import { organizationStore } from '@/store/organization'

const STORAGE_KEY = 'otklik.organizationId'

function makeOrganization(overrides: Partial<Organization> = {}): Organization {
  return {
    id: 11,
    url: 'https://yandex.ru/maps/org/11',
    yandex_business_id: '11',
    name: 'Кафе',
    avg_rating: 4.6,
    ratings_count: 20,
    reviews_count: 20,
    parse_status: 'success',
    last_parsed_at: '2024-03-15T10:00:00.000Z',
    rating_breakdown: [{ rating: 5, count: 12 }],
    last_parse_duration_seconds: 15,
    ...overrides,
  }
}

function makeReviews(id = 1): ReviewsResponse {
  return {
    data: [{
      id,
      yandex_review_id: String(id),
      author: 'Иван',
      rating: 5,
      text: 'Хорошо',
      business_reply: null,
      published_at: '2024-03-15T10:00:00.000Z',
    }],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 50,
      total: 12,
    },
  }
}

function resetStore() {
  organizationStore.organization = null
  organizationStore.reviews = []
  organizationStore.reviewsMeta = null
  organizationStore.isSaving = false
  organizationStore.isLoadingReviews = false
  organizationStore.urlError = null
  organizationStore.restored = false
}

describe('organizationStore', () => {
  beforeEach(() => {
    resetStore()
    localStorage.clear()
    vi.useFakeTimers()
    vi.mocked(createOrganization).mockReset()
    vi.mocked(getOrganization).mockReset()
    vi.mocked(getOrganizationReviews).mockReset()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('восстанавливается без сохранённого id', async () => {
    await organizationStore.restore()

    expect(organizationStore.restored).toBe(true)
    expect(getOrganization).not.toHaveBeenCalled()
  })

  it('подтягивает организацию из localStorage', async () => {
    localStorage.setItem(STORAGE_KEY, '21')
    vi.mocked(getOrganization).mockResolvedValue(makeOrganization({ id: 21 }))

    await organizationStore.restore()

    expect(getOrganization).toHaveBeenCalledWith(21)
    expect(organizationStore.organization?.id).toBe(21)
    expect(organizationStore.restored).toBe(true)
  })

  it('забывает битый id', async () => {
    localStorage.setItem(STORAGE_KEY, '99')
    vi.mocked(getOrganization).mockRejectedValue(new Error('404'))

    await organizationStore.restore()

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
    expect(organizationStore.organization).toBeNull()
    expect(organizationStore.restored).toBe(true)
  })

  it('сохраняет ссылку и пишет id', async () => {
    const organization = makeOrganization({ id: 31, parse_status: 'pending' })
    vi.mocked(createOrganization).mockResolvedValue(organization)
    vi.mocked(getOrganization).mockResolvedValue({ ...organization, parse_status: 'success' })
    vi.mocked(getOrganizationReviews).mockResolvedValue(makeReviews(31))

    await organizationStore.saveUrl(organization.url)

    expect(createOrganization).toHaveBeenCalledWith(organization.url)
    expect(localStorage.getItem(STORAGE_KEY)).toBe('31')
    expect(organizationStore.isSaving).toBe(false)
    expect(organizationStore.urlError).toBeNull()
  })

  it('кладёт ошибку валидации из 422', async () => {
    const error = new AxiosError('Unprocessable')
    error.response = {
      status: 422,
      data: { errors: { url: ['Ссылка не подходит.'] } },
    } as AxiosError['response']

    vi.mocked(createOrganization).mockRejectedValue(error)

    await expect(organizationStore.saveUrl('https://example.com')).rejects.toBe(error)
    expect(organizationStore.urlError).toBe('Ссылка не подходит.')
    expect(organizationStore.isSaving).toBe(false)
  })

  it('загружает отзывы и не ходит в API повторно за той же страницей', async () => {
    organizationStore.organization = makeOrganization({ id: 41 })
    vi.mocked(getOrganizationReviews).mockResolvedValue(makeReviews(41))

    await organizationStore.loadReviews(1, { sort: 'newest' })
    await organizationStore.loadReviews(1, { sort: 'newest' })

    expect(getOrganizationReviews).toHaveBeenCalledTimes(1)
    expect(organizationStore.reviews).toHaveLength(1)
    expect(organizationStore.reviewsMeta?.total).toBe(12)
    expect(organizationStore.isLoadingReviews).toBe(false)
  })
})
