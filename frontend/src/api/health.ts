import { apiClient } from './axios'
import type { HealthResponse } from '@/types/api'

export function getHealth() {
  return apiClient.get<HealthResponse>('/health').then((response) => response.data)
}
