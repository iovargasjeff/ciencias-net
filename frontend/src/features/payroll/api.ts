import { apiClient } from '@/lib/api/client'
import type { Page } from '@/features/phase-one/types'
import type {
  TeacherAttendance,
  TeacherRate,
  PayrollLiquidation,
  TeacherAttendanceFilters,
} from './types'

const getData = <T>(response: { data: { data: T } }) => response.data.data

export async function listTeacherAttendance(
  filters?: TeacherAttendanceFilters
): Promise<Page<TeacherAttendance>> {
  const params = new URLSearchParams()
  if (filters) {
    if (filters.date) params.append('date', filters.date)
    if (filters.status) params.append('status', filters.status)
    if (filters.search) params.append('search', filters.search)
  }
  const response = await apiClient.get<Page<TeacherAttendance>>('/api/v1/teacher-attendance', {
    params,
  })
  return response.data
}

export async function listTeacherRates(): Promise<Page<TeacherRate>> {
  const response = await apiClient.get<Page<TeacherRate>>('/api/v1/teacher-rates')
  return response.data
}

export async function createTeacherRate(input: {
  teacher_id: string
  hourly_rate: string
  effective_from: string
  effective_until?: string | null
}): Promise<TeacherRate> {
  return getData(await apiClient.post('/api/v1/teacher-rates', input))
}

export async function listPayrollLiquidations(): Promise<Page<PayrollLiquidation>> {
  const response = await apiClient.get<Page<PayrollLiquidation>>('/api/v1/payroll-liquidations')
  return response.data
}

export async function createPayrollLiquidation(input: {
  period_start: string
  period_end: string
  teacher_ids?: string[]
}): Promise<PayrollLiquidation> {
  const idempotencyKey = crypto.randomUUID()
  return getData(
    await apiClient.post('/api/v1/payroll-liquidations', input, {
      headers: {
        'Idempotency-Key': idempotencyKey,
      },
    })
  )
}

export async function closePayrollLiquidation(liquidationId: string): Promise<{ id: string }> {
  return getData(await apiClient.post(`/api/v1/payroll-liquidations/${liquidationId}/closure`))
}

export async function createTeacherAttendanceAdjustment(input: {
  teacher_id: string
  date: string
  adjustment_type: 'add' | 'subtract'
  minutes: number
  reason: string
}): Promise<{ id: string }> {
  return getData(await apiClient.post('/api/v1/teacher-attendance/adjustments', input))
}

export async function cancelClassSession(
  classSessionId: string,
  reason: string
): Promise<{ id: string }> {
  return getData(
    await apiClient.post(`/api/v1/class-sessions/${classSessionId}/cancellation`, { reason })
  )
}

export async function assignClassSessionSubstitute(
  classSessionId: string,
  input: {
    teacher_id: string
  }
): Promise<{ id: string }> {
  return getData(
    await apiClient.put(`/api/v1/class-sessions/${classSessionId}/substitute`, input)
  )
}

export async function generateTeacherPayrollReport(input: {
  period_start: string
  period_end: string
  format: 'pdf' | 'xlsx'
  teacher_ids?: string[]
}): Promise<{ message?: string; file_url?: string }> {
  const idempotencyKey = crypto.randomUUID()
  return getData(
    await apiClient.post('/api/v1/teacher-attendance/reports', input, {
      headers: {
        'Idempotency-Key': idempotencyKey,
      },
    })
  )
}
