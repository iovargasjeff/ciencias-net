import { client } from '../../lib/api/client'
import type { PaginatedResponse, ResourceResponse } from '../../lib/api/types'
import type { PsychologyCare, CreatePsychologyCareRequest } from './types'

export const listPsychologyCare = async (params?: Record<string, string>): Promise<PaginatedResponse<PsychologyCare>> => {
  const { data } = await client.get<PaginatedResponse<PsychologyCare>>('/api/v1/psychology', { params })
  return data
}

export const createPsychologyCare = async (request: CreatePsychologyCareRequest): Promise<ResourceResponse<{ id: string }>> => {
  const { data } = await client.post<ResourceResponse<{ id: string }>>('/api/v1/psychology', request)
  return data
}
