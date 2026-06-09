export interface PsychologyCare {
  id: string
  student_id: string
  occurred_at: string
  summary: string
  confidential_notes?: string
  incident_id?: string
  created_at: string
  updated_at: string
}

export interface CreatePsychologyCareRequest {
  student_id: string
  occurred_at: string
  summary: string
  confidential_notes?: string
  incident_id?: string
}
