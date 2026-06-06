import axios from 'axios'
import { apiClient, initializeCsrf } from '@/lib/api/client'
import type { AuthUser, LoginInput } from '@/features/auth/types'

export async function getSession(): Promise<AuthUser | null> {
  try {
    const response = await apiClient.get<{ data: AuthUser }>('/api/v1/auth/session')
    return response.data.data
  } catch (error: unknown) {
    if (axios.isAxiosError(error) && error.response?.status === 401) return null
    throw error
  }
}

export async function login(input: LoginInput): Promise<AuthUser> {
  await initializeCsrf()
  const response = await apiClient.post<{ data: AuthUser }>('/api/v1/auth/login', input)
  return response.data.data
}

export async function logout(): Promise<void> {
  await apiClient.post('/api/v1/auth/logout')
}

export async function requestPasswordRecovery(email: string): Promise<string> {
  const response = await apiClient.post<{ data: { message: string } }>('/api/v1/auth/forgot-password', { email })
  return response.data.data.message
}
