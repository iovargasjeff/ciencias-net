import { apiClient } from '@/lib/api/client'
import type { Page } from '@/features/phase-one/types'
import type {
  Schedule,
  CreateScheduleInput,
  CalendarEvent,
  CreateCalendarEventInput
} from './types'

const data = <T>(response: { data: { data: T } }) => response.data.data

export async function listSchedules(params?: {
  teaching_assignment_id?: string
  section_id?: string
  teacher_id?: string
  student_id?: string
}): Promise<Page<Schedule>> {
  return (await apiClient.get<Page<Schedule>>('/api/v1/schedules', { params })).data
}

export async function createSchedule(input: CreateScheduleInput): Promise<Schedule> {
  const response = await apiClient.post<{ data: Schedule }>('/api/v1/schedules', input)
  return response.data.data
}

export async function listCalendarEvents(params?: {
  start_date?: string
  end_date?: string
}): Promise<Page<CalendarEvent>> {
  return (await apiClient.get<Page<CalendarEvent>>('/api/v1/calendar-events', { params })).data
}

export async function createCalendarEvent(input: CreateCalendarEventInput): Promise<CalendarEvent> {
  return data(await apiClient.post('/api/v1/calendar-events', input))
}
