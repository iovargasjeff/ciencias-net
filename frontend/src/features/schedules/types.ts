export interface Schedule {
  id: string
  teaching_assignment_id: string
  weekday: number // 1 (Lunes) to 7 (Domingo)
  starts_at: string // format "HH:MM"
  ends_at: string // format "HH:MM"
  room: string | null
  created_at: string
  updated_at: string
}

export interface CreateScheduleInput {
  teaching_assignment_id: string
  weekday: number
  starts_at: string
  ends_at: string
  room?: string
}

export interface CalendarEvent {
  id: string
  title: string
  starts_at: string // format ISO Date-Time
  ends_at: string // format ISO Date-Time
  event_type: 'academic' | 'holiday' | 'meeting' | 'other'
  description: string | null
  created_at: string
  updated_at: string
}

export interface CreateCalendarEventInput {
  title: string
  starts_at: string
  ends_at: string
  event_type: 'academic' | 'holiday' | 'meeting' | 'other'
  description?: string
}
