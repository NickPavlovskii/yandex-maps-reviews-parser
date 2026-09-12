import { apiClient } from './axios'
import type { Organization, OrganizationResponse, ReviewsResponse } from '@/types/api'

export const REVIEWS_PER_PAGE = 50

export type ReviewFilters = {
  page?: number
  rating?: number | null
  q?: string
  sort?: 'newest' | 'oldest'
}

export async function createOrganization(url: string): Promise<Organization> {
  const response = await apiClient.post<OrganizationResponse>('/organizations', { url })

  return response.data.data
}

export async function getOrganization(id: number): Promise<Organization> {
  const response = await apiClient.get<OrganizationResponse>(`/organizations/${id}`)

  return response.data.data
}

export async function getOrganizationReviews(
  id: number,
  filters: ReviewFilters = {},
): Promise<ReviewsResponse> {
  const response = await apiClient.get<ReviewsResponse>(`/organizations/${id}/reviews`, {
    params: {
      page: filters.page ?? 1,
      per_page: REVIEWS_PER_PAGE,
      rating: filters.rating || undefined,
      q: filters.q || undefined,
      sort: filters.sort ?? 'newest',
    },
  })

  return response.data
}
