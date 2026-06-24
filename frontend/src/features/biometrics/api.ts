import { apiClient } from '@/lib/api/client'
import type { Page } from '@/features/phase-one/types'
import type { BiometricConsent, Station, StationCamera, ActivationCodeResponse } from './types'

const getData = <T>(response: { data: { data: T } }) => response.data.data

export async function listBiometricConsents(): Promise<Page<BiometricConsent>> {
  return (await apiClient.get<Page<BiometricConsent>>('/api/v1/biometric-consents')).data
}

export interface StudentLookup {
  id: string
  user_id: string
  dni: string
  name: string
}

export async function searchStudents(search: string): Promise<StudentLookup[]> {
  return getData(await apiClient.get('/api/v1/search/students', { params: { search } }))
}

export async function grantBiometricConsent(input: {
  student_id: string
  legal_basis: string
  expires_at?: string
}): Promise<BiometricConsent> {
  return getData(await apiClient.post('/api/v1/biometric-consents', input))
}

export async function revokeBiometricConsent(consentId: string, reason: string): Promise<BiometricConsent> {
  return getData(await apiClient.post(`/api/v1/biometric-consents/${consentId}/revocation`, { reason }))
}

export async function enrollBiometricProfile(
  student_id: string,
  consent_id: string,
  images: File[]
): Promise<{ id: string }> {
  const formData = new FormData()
  formData.append('student_id', student_id)
  formData.append('consent_id', consent_id)
  images.forEach((file) => {
    formData.append('images[]', file)
  })
  return getData(
    await apiClient.post('/api/v1/biometric-enrollments', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  )
}

export async function listStations(): Promise<Page<Station>> {
  return (await apiClient.get<Page<Station>>('/api/v1/stations')).data
}

export async function createStation(input: {
  name: string
  location: string
  mode: 'entry' | 'exit' | 'mixed'
}): Promise<Station> {
  return getData(await apiClient.post('/api/v1/stations', input))
}

export async function updateStation(
  stationId: string,
  input: { name?: string; location?: string; mode?: 'entry' | 'exit' | 'mixed'; active?: boolean }
): Promise<Station> {
  return getData(await apiClient.patch(`/api/v1/stations/${stationId}`, input))
}

export async function revokeStation(stationId: string, reason: string): Promise<Station> {
  return getData(await apiClient.post(`/api/v1/stations/${stationId}/revocation`, { reason }))
}

export async function listStationCameras(stationId: string): Promise<StationCamera[]> {
  return getData(await apiClient.get(`/api/v1/stations/${stationId}/cameras`))
}

export async function createStationCamera(
  stationId: string,
  input: { label: string; device_identifier: string; active?: boolean }
): Promise<StationCamera> {
  return getData(await apiClient.post(`/api/v1/stations/${stationId}/cameras`, input))
}

export async function createStationActivationCode(stationId: string): Promise<ActivationCodeResponse> {
  return getData(await apiClient.post(`/api/v1/stations/${stationId}/activation-codes`))
}
