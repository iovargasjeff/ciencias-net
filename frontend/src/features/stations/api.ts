import { apiClient } from '@/lib/api/client'
import type { Station, StationCaptureResponse } from './types'

export async function activateStation(activation_code: string, device_name: string): Promise<{ id: string }> {
  const response = await apiClient.post<{ data: { id: string } }>('/api/v1/station-activations', {
    activation_code,
    device_name,
  })
  return response.data.data
}

export async function getStationSession(): Promise<Station> {
  const response = await apiClient.get<{ data: Station }>('/api/v1/station/session')
  return response.data.data
}

export async function submitStationCapture(
  image: Blob,
  camera_id: string,
  captured_at: string,
  idempotencyKey: string
): Promise<StationCaptureResponse> {
  const formData = new FormData()
  formData.append('image', image, 'capture.jpg')
  formData.append('camera_id', camera_id)
  formData.append('captured_at', captured_at)

  const response = await apiClient.post<{ data: StationCaptureResponse }>(
    '/api/v1/station/captures',
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Idempotency-Key': idempotencyKey,
      },
    }
  )
  return response.data.data
}
