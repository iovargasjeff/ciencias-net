export interface StudentAttendance {
  id: string
  student_id: string
  student_name: string
  grade: string
  section: string
  date: string // YYYY-MM-DD
  status: 'present' | 'late' | 'absent' | 'excused'
  entry_time: string | null // ISO Date-Time string
  exit_time: string | null // ISO Date-Time string
  justified: boolean
  justification_reason: string | null
  reason?: string | null
  created_at?: string
}

export interface StudentAttendanceAnomaly {
  id: string
  student_id: string
  student_name: string
  grade: string
  section: string
  date: string
  entry_time: string
  exit_time: string | null
  status: 'pending' | 'resolved'
  resolution_reason?: string | null
  resolved_at?: string | null
}

export interface RecognitionEvent {
  id: string
  station_id: string
  station_name: string
  captured_at: string
  confidence: number
  status: 'pending' | 'confirmed' | 'rejected' | 'reassigned'
  image_url?: string | null
  matched_student_id?: string | null
  matched_student_name?: string | null
  outcome?: 'confirmed' | 'rejected' | 'reassigned' | null
  reason?: string | null
}

export interface AttendanceFilters {
  date?: string
  grade?: string
  section?: string
  status?: string
  search?: string
}
