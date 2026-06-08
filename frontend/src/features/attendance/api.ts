import { apiClient } from '@/lib/api/client'
import type { Page } from '@/features/phase-one/types'
import type {
  StudentAttendance,
  StudentAttendanceAnomaly,
  RecognitionEvent,
  AttendanceFilters,
} from './types'

const getData = <T>(response: { data: { data: T } }) => response.data.data

export async function listStudentAttendance(
  filters?: AttendanceFilters
): Promise<Page<StudentAttendance>> {
  const params = new URLSearchParams()
  if (filters) {
    if (filters.date) params.append('date', filters.date)
    if (filters.grade) params.append('grade', filters.grade)
    if (filters.section) params.append('section', filters.section)
    if (filters.status) params.append('status', filters.status)
    if (filters.search) params.append('search', filters.search)
  }
  const response = await apiClient.get<Page<StudentAttendance>>('/api/v1/student-attendance', {
    params,
  })
  return response.data
}

export async function createManualStudentAttendanceEvent(input: {
  student_id: string
  event_type: 'entry' | 'exit' | 'absence' | 'late'
  occurred_at: string
  reason: string
}): Promise<StudentAttendance> {
  const idempotencyKey = crypto.randomUUID()
  return getData(
    await apiClient.post('/api/v1/student-attendance/manual-events', input, {
      headers: {
        'Idempotency-Key': idempotencyKey,
      },
    })
  )
}

export async function closeStudentAttendanceDay(input: {
  date: string
}): Promise<{ message?: string }> {
  const idempotencyKey = crypto.randomUUID()
  return getData(
    await apiClient.post('/api/v1/student-attendance/day-closures', input, {
      headers: {
        'Idempotency-Key': idempotencyKey,
      },
    })
  )
}

export async function listStudentAttendanceAnomalies(): Promise<Page<StudentAttendanceAnomaly>> {
  const response = await apiClient.get<Page<StudentAttendanceAnomaly>>(
    '/api/v1/student-attendance/anomalies'
  )
  return response.data
}

export async function resolveStudentAttendanceAnomaly(
  anomalyId: string,
  reason: string
): Promise<{ id: string }> {
  return getData(
    await apiClient.post(`/api/v1/student-attendance/anomalies/${anomalyId}/resolution`, {
      reason,
    })
  )
}

export async function justifyStudentAbsence(
  attendanceId: string,
  reason: string
): Promise<{ id: string }> {
  return getData(
    await apiClient.post(`/api/v1/student-attendance/absences/${attendanceId}/justification`, {
      reason,
    })
  )
}

export async function listRecognitionEventsForReview(): Promise<Page<RecognitionEvent>> {
  const response = await apiClient.get<Page<RecognitionEvent>>('/api/v1/recognition-events')
  return response.data
}

export async function reviewRecognitionEvent(
  recognitionEventId: string,
  input: {
    outcome: 'confirmed' | 'rejected' | 'reassigned'
    reason: string
    matched_student_id?: string
  }
): Promise<{ id: string }> {
  return getData(
    await apiClient.post(`/api/v1/recognition-events/${recognitionEventId}/review`, input)
  )
}
