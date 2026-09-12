import { reactive } from 'vue'
import { isAxiosError } from 'axios'
import {
  createOrganization,
  getOrganization,
  getOrganizationReviews,
  type ReviewFilters,
} from '@/api/organizations'
import type { Organization, Review, ReviewsMeta, ReviewsResponse } from '@/types/api'

const STORAGE_KEY = 'otklik.organizationId'

const TERMINAL_STATUSES = new Set([
  'success',
  'failed_structure_changed',
  'failed_blocked',
  'failed_unavailable',
])

const POLL_INTERVAL_MS = 3000
const POLL_RETRY_INTERVAL_MS = 5000

let pollTimer: ReturnType<typeof setTimeout> | null = null
const reviewsCache = new Map<string, ReviewsResponse>()

function reviewsCacheKey(id: number, page: number, filters: ReviewFilters) {
  return [id, page, filters.rating ?? '', filters.q ?? '', filters.sort ?? 'newest'].join(':')
}

function persistId(id: number) {
  localStorage.setItem(STORAGE_KEY, String(id))
}

function stopPolling() {
  if (pollTimer) {
    clearTimeout(pollTimer)
    pollTimer = null
  }
}

export const organizationStore = reactive({
  organization: null as Organization | null,
  reviews: [] as Review[],
  reviewsMeta: null as ReviewsMeta | null,
  isSaving: false,
  isLoadingReviews: false,
  urlError: null as string | null,
  restored: false,

  async restore() {
    if (this.organization) {
      this.restored = true

      if (!TERMINAL_STATUSES.has(this.organization.parse_status)) {
        void this.pollStatus(this.organization.id)
      }

      return
    }

    const raw = localStorage.getItem(STORAGE_KEY)
    const id = Number(raw)

    if (!id) {
      this.restored = true
      return
    }

    try {
      this.organization = await getOrganization(id)

      if (!TERMINAL_STATUSES.has(this.organization.parse_status)) {
        void this.pollStatus(id)
      }
    } catch {
      localStorage.removeItem(STORAGE_KEY)
    } finally {
      this.restored = true
    }
  },

  async loadReviews(page = 1, filters: ReviewFilters = {}) {
    if (!this.organization) {
      return
    }

    const key = reviewsCacheKey(this.organization.id, page, filters)
    const cached = reviewsCache.get(key)

    if (cached) {
      this.reviews = cached.data
      this.reviewsMeta = cached.meta
      this.prefetchAround(page, filters)
      return
    }

    this.isLoadingReviews = true

    try {
      const payload = await getOrganizationReviews(this.organization.id, {
        ...filters,
        page,
      })
      reviewsCache.set(key, payload)
      this.reviews = payload.data
      this.reviewsMeta = payload.meta
      this.prefetchAround(page, filters)
    } finally {
      this.isLoadingReviews = false
    }
  },

  prefetchAround(page: number, filters: ReviewFilters) {
    const last = this.reviewsMeta?.last_page ?? 1
    const neighbors = [page - 1, page + 1].filter((item) => item >= 1 && item <= last)

    for (const neighbor of neighbors) {
      void this.prefetchReviews(neighbor, filters)
    }
  },

  async prefetchReviews(page: number, filters: ReviewFilters = {}) {
    if (!this.organization) {
      return
    }

    const key = reviewsCacheKey(this.organization.id, page, filters)

    if (reviewsCache.has(key)) {
      return
    }

    try {
      const payload = await getOrganizationReviews(this.organization.id, {
        ...filters,
        page,
      })
      reviewsCache.set(key, payload)
    } catch {
      // соседнюю страницу подтянем при открытии
    }
  },

  async pollStatus(id: number) {
    try {
      this.organization = await getOrganization(id)

      if (!TERMINAL_STATUSES.has(this.organization.parse_status)) {
        pollTimer = setTimeout(() => this.pollStatus(id), POLL_INTERVAL_MS)
        return
      }

      if (this.organization.parse_status === 'success') {
        await this.loadReviews()
      }
    } catch {
      pollTimer = setTimeout(() => this.pollStatus(id), POLL_RETRY_INTERVAL_MS)
    }
  },

  async saveUrl(url: string) {
    this.urlError = null
    this.isSaving = true
    this.reviews = []
    this.reviewsMeta = null
    reviewsCache.clear()
    stopPolling()

    try {
      this.organization = await createOrganization(url)
      persistId(this.organization.id)
      void this.pollStatus(this.organization.id)
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 422) {
        const messages = error.response.data?.errors as Record<string, string[]> | undefined
        this.urlError = messages?.url?.[0] ?? 'Ссылка не подходит.'
      } else {
        this.urlError = 'Не удалось сохранить ссылку. Попробуйте ещё раз.'
      }

      throw error
    } finally {
      this.isSaving = false
    }
  },
})
