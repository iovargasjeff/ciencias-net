import { apiClient } from '@/lib/api/client'
import type { AxiosProgressEvent } from 'axios'
import type { Page } from '@/features/phase-one/types'
import type {
  Material,
  CreateMaterialInput,
  CreateExternalMaterialInput,
  UpdateMaterialInput
} from './types'

const data = <T>(response: { data: { data: T } }) => response.data.data

export async function listMaterials(params?: {
  teaching_assignment_id?: string
  student_id?: string
  week?: number
}): Promise<Page<Material>> {
  return (await apiClient.get<Page<Material>>('/api/v1/materials', { params })).data
}

export async function createMaterial(
  input: CreateMaterialInput,
  onProgress?: (progressEvent: AxiosProgressEvent) => void
): Promise<Material> {
  const formData = new FormData()
  formData.append('title', input.title)
  if (input.description) formData.append('description', input.description)
  formData.append('teaching_assignment_id', input.teaching_assignment_id)
  if (input.week !== undefined) formData.append('week', String(input.week))
  formData.append('file', input.file)

  const response = await apiClient.post<{ data: Material }>('/api/v1/materials', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    },
    onUploadProgress: onProgress
  })
  return response.data.data
}

export async function createExternalMaterial(input: CreateExternalMaterialInput): Promise<Material> {
  return data(await apiClient.post('/api/v1/material-links', input))
}

export async function updateMaterial(id: string, input: UpdateMaterialInput): Promise<Material> {
  return data(await apiClient.patch(`/api/v1/materials/${id}`, input))
}

export async function archiveMaterial(id: string): Promise<void> {
  await apiClient.delete(`/api/v1/materials/${id}`)
}

export async function replaceMaterialFile(
  id: string,
  file: File,
  onProgress?: (progressEvent: AxiosProgressEvent) => void
): Promise<Material> {
  const formData = new FormData()
  formData.append('file', file)

  const response = await apiClient.put<{ data: Material }>(
    `/api/v1/materials/${id}/file`,
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: onProgress
    }
  )
  return response.data.data
}

export async function downloadMaterial(id: string): Promise<Blob> {
  const response = await apiClient.get(`/api/v1/materials/${id}/download`, {
    responseType: 'blob'
  })
  return response.data
}
