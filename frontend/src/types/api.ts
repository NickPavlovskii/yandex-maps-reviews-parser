export type HealthResponse = {
  status: string
  cache: string
  queue: string
  redis: string
}

export type User = {
  id: number
  name: string
  email: string
}

export type UserResponse = {
  data: User
}

export type ParseStatus =
  | 'pending'
  | 'in_progress'
  | 'success'
  | 'failed_structure_changed'
  | 'failed_blocked'
  | 'failed_unavailable'

export type Organization = {
  id: number
  url: string
  yandex_business_id: string
  name: string | null
  avg_rating: number | null
  ratings_count: number | null
  reviews_count: number | null
  parse_status: ParseStatus
  last_parsed_at: string | null
  rating_breakdown: Array<{ rating: number; count: number }>
  last_parse_duration_seconds: number | null
}

export type OrganizationResponse = {
  data: Organization
}

export type Review = {
  id: number
  yandex_review_id: string
  author: string | null
  rating: number | null
  text: string | null
  business_reply: string | null
  published_at: string | null
}

export type ReviewsMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type ReviewsResponse = {
  data: Review[]
  meta: ReviewsMeta
}
