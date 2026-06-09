export interface Material {
  id: string
  title: string
  description: string | null
  teaching_assignment_id: string
  week: number | null
  type: 'file' | 'link'
  url: string | null
  file_id: string | null
  file_name: string | null
  file_size: number | null
  created_at: string
  updated_at: string
}

export interface CreateMaterialInput {
  title: string
  description?: string
  teaching_assignment_id: string
  week?: number
  file: File
}

export interface CreateExternalMaterialInput {
  title: string
  description?: string
  teaching_assignment_id: string
  week?: number
  url: string
}

export interface UpdateMaterialInput {
  title?: string
  description?: string
  week?: number
}
