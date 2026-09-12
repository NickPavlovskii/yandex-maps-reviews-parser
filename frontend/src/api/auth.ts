import { http } from './axios'
import type { User, UserResponse } from '@/types/api'

export async function fetchCsrfCookie() {
  await http.get('/sanctum/csrf-cookie')
}

export async function login(email: string, password: string, remember = false): Promise<User> {
  await fetchCsrfCookie()

  const response = await http.post<UserResponse>('/login', { email, password, remember })

  return response.data.data
}

export async function logout() {
  await http.post('/logout')
}

export async function getUser(): Promise<User> {
  const response = await http.get<UserResponse>('/user')

  return response.data.data
}
