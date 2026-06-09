export interface Incident {
  id: string
  student_id: string
  incident_type: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  description: string
  status: 'open' | 'referred_toe' | 'referred_psychology' | 'parent_notified' | 'in_progress' | 'resolved' | 'closed'
  occurred_at: string
  created_at: string
  updated_at: string
}

export interface IncidentTimelineEvent {
  id: string
  incident_id: string
  action: string
  note?: string
  created_by: string
  created_at: string
}

export interface CreateIncidentRequest {
  student_id: string
  incident_type: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  description: string
  occurred_at: string
}

export interface TransitionIncidentRequest {
  target_status: 'open' | 'referred_toe' | 'referred_psychology' | 'parent_notified' | 'in_progress' | 'resolved' | 'closed'
  reason: string
}

export interface CreateIncidentFollowUpRequest {
  note: string
  file?: File | null
}

export interface GenerateIncidentReportRequest {
  from_date: string
  to_date: string
  format: 'pdf' | 'xlsx'
  status?: string
  severity?: string
}
