export interface StationCamera {
  id: string
  label: string
  device_identifier: string
  active: boolean
  mode: 'entry' | 'exit' | 'mixed'
}

export interface Station {
  id: string
  name: string
  location: string
  mode: 'entry' | 'exit' | 'mixed'
  cameras: StationCamera[]
}

export interface StationCaptureInput {
  image: Blob
  camera_id: string
  captured_at: string
}

export interface StationCaptureResponse {
  id: string
  outcome: 'accepted' | 'review' | 'rejected'
  score: number
  student_name?: string
  occurred_at: string
}
