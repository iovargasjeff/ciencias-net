export interface Page<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}

export interface Account {
  id: string
  name: string
  email: string
  active: boolean
  roles: string[]
  last_login_at: string | null
  created_at: string
}

export interface FamilyLink {
  id: string
  student_id: string
  parent_account_id: string
  student_name: string
  parent_name: string
  relationship: 'padre' | 'madre' | 'apoderado'
}

export interface LinkedStudent {
  id: string
  name: string
  relationship: string
}

export interface StudentSummary {
  id: string
  name: string
  biometric_status: string
  enrollments: Array<{ id: string; section: string; grade: string; academic_period: string }>
}

export interface AcademicItem {
  id: string
  name?: string
  code?: string
  status?: string
  level?: string
  student_id?: string
  teacher_id?: string
  section_id?: string
  course_id?: string
  grade_id?: string
  academic_period_id?: string
  valid_from?: string
  valid_until?: string | null
  active?: boolean
}
