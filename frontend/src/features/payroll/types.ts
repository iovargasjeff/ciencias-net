export interface TeacherAttendance {
  id: string
  date: string // YYYY-MM-DD
  class_session_id: string
  teacher_id: string
  teacher_name: string
  status: 'present' | 'late' | 'absent' | 'excused' | 'cancelled'
  entry_time: string | null // ISO datetime
  exit_time: string | null // ISO datetime
  hourly_rate: string
  minutes_late: number
  hours_absent: number
  substitute_teacher_id: string | null
  substitute_teacher_name: string | null
  reason: string | null
}

export interface TeacherRate {
  id: string
  teacher_id: string
  teacher_name: string
  hourly_rate: string // decimal string, e.g., "20.00"
  effective_from: string // YYYY-MM-DD
  effective_until: string | null // YYYY-MM-DD
}

export interface PayrollLiquidationItem {
  id: string
  teacher_id: string
  teacher_name: string
  regular_hours: number
  hours_absent_justified: number
  hours_absent_unjustified: number
  minutes_late: number
  hourly_rate: string
  total_discount: number
}

export interface PayrollLiquidation {
  id: string
  period_start: string // YYYY-MM-DD
  period_end: string // YYYY-MM-DD
  status: 'open' | 'closed'
  total_teachers: number
  total_discount: number
  created_at: string
  closed_at: string | null
  items?: PayrollLiquidationItem[]
}

export interface TeacherAttendanceFilters {
  date?: string
  status?: string
  search?: string
}
