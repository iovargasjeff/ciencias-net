import { apiClient } from '@/lib/api/client'
import type { Page } from '@/features/phase-one/types'
import type {
  Announcement,
  CreateAnnouncementInput,
  Notification
} from './types'

const getResponseData = <T>(response: { data: { data: T } }) => response.data.data

export async function listAnnouncements(params?: {
  is_archived?: boolean
}): Promise<Page<Announcement>> {
  return (await apiClient.get<Page<Announcement>>('/api/v1/announcements', { params })).data
}

export async function createAnnouncement(input: CreateAnnouncementInput): Promise<Announcement> {
  return getResponseData(await apiClient.post<{ data: Announcement }>('/api/v1/announcements', input))
}

export async function markAnnouncementRead(id: string): Promise<void> {
  await apiClient.put(`/api/v1/announcements/${id}/read`)
}

export async function archiveAnnouncement(id: string): Promise<void> {
  await apiClient.put(`/api/v1/announcements/${id}/archive`)
}

export async function listNotifications(): Promise<Page<Notification>> {
  return (await apiClient.get<Page<Notification>>('/api/v1/notifications')).data
}
