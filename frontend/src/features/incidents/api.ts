import { apiClient } from '@/lib/api/client'
import type { PaginatedResponse, ResourceResponse } from '@/lib/api/types'
import type {
  Incident,
  CreateIncidentRequest,
  TransitionIncidentRequest,
  CreateIncidentFollowUpRequest,
  GenerateIncidentReportRequest
} from './types'

export async function listIncidents(params?: Record<string, unknown>): Promise<PaginatedResponse<Incident>> {
  const response = await apiClient.get<PaginatedResponse<Incident>>('/api/v1/incidents', { params })
  return response.data
}

export async function createIncident(data: CreateIncidentRequest): Promise<ResourceResponse<Incident>> {
  const response = await apiClient.post<ResourceResponse<Incident>>('/api/v1/incidents', data)
  return response.data
}

export async function transitionIncident(incidentId: string, data: TransitionIncidentRequest): Promise<ResourceResponse<Incident>> {
  const response = await apiClient.post<ResourceResponse<Incident>>(`/api/v1/incidents/${incidentId}/transitions`, data)
  return response.data
}

export async function createIncidentFollowUp(incidentId: string, data: CreateIncidentFollowUpRequest): Promise<ResourceResponse<Incident>> {
  const formData = new FormData()
  formData.append('note', data.note)
  if (data.file) {
    formData.append('file', data.file)
  }

  const response = await apiClient.post<ResourceResponse<Incident>>(`/api/v1/incidents/${incidentId}/follow-ups`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
  return response.data
}

export async function generateIncidentReport(data: GenerateIncidentReportRequest, idempotencyKey: string): Promise<Blob> {
  const response = await apiClient.post('/api/v1/incidents/reports', data, {
    headers: { 'Idempotency-Key': idempotencyKey },
    responseType: 'blob'
  })
  return response.data
}
