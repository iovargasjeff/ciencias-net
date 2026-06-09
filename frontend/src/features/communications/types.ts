export interface Announcement {
  id: string
  title: string
  body: string
  audience_type: 'all' | 'roles' | 'sections' | 'accounts'
  audience_ids?: string[]
  publish_at?: string
  is_read: boolean
  is_archived: boolean
  created_at: string
  updated_at: string
}

export interface CreateAnnouncementInput {
  title: string
  body: string
  audience_type: 'all' | 'roles' | 'sections' | 'accounts'
  audience_ids?: string[]
  publish_at?: string
}

export interface Notification {
  id: string
  title: string
  body: string
  is_read: boolean
  created_at: string
}
