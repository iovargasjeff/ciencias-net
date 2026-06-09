export interface BiometricConsent {
  id: string
  student_id: string
  student_name?: string
  legal_basis: string
  expires_at?: string
  revoked_at?: string
  status: 'active' | 'revoked' | 'expired'
  created_at?: string
}

export interface Station {
  id: string
  name: string
  location: string
  mode: 'entry' | 'exit' | 'mixed'
  active: boolean
  status: 'active' | 'inactive' | 'revoked'
  created_at?: string
}

export interface StationCamera {
  id: string
  station_id: string
  label: string
  device_identifier: string
  active: boolean
}

export interface ActivationCodeResponse {
  id: string
  activation_code: string
  expires_at: string
}
