import { apiClient } from '@/lib/api/client'
import type { Account, AcademicItem, FamilyLink, LinkedStudent, Page, StudentSummary } from './types'

const data = <T>(response: { data: { data: T } }) => response.data.data

export async function listAccounts(search = '', excludeRoles = ''): Promise<Page<Account>> {
  return (await apiClient.get<Page<Account>>('/api/v1/accounts', { params: { search, exclude_roles: excludeRoles } })).data
}

export async function searchDni(type: 'students' | 'parents' | 'teachers', dni: string): Promise<{ id: string; user_id: string; dni: string; name: string } | null> {
  try {
    return data(await apiClient.get(`/api/v1/search/${type}`, { params: { dni } }))
  } catch {
    return null;
  }
}
export async function createAccount(input: { name: string; email: string; roles: string[] }): Promise<Account> {
  return data(await apiClient.post('/api/v1/accounts', input))
}
export async function setAccountRoles(id: string, roles: string[]): Promise<Account> {
  return data(await apiClient.put(`/api/v1/accounts/${id}/roles`, { roles }))
}
export async function setAccountActive(id: string, active: boolean): Promise<Account> {
  return data(await apiClient.put(`/api/v1/accounts/${id}/activation`, { active }))
}

export async function listFamilyLinks(): Promise<Page<FamilyLink>> {
  return (await apiClient.get<Page<FamilyLink>>('/api/v1/family-links')).data
}
export async function createFamilyLink(input: { parent_account_id: string; student_id: string; relationship: string }): Promise<FamilyLink> {
  return data(await apiClient.post('/api/v1/family-links', input))
}
export async function removeFamilyLink(id: string): Promise<void> {
  await apiClient.delete(`/api/v1/family-links/${id}`)
}
export async function listLinkedStudents(): Promise<LinkedStudent[]> {
  return data(await apiClient.get('/api/v1/family/students'))
}
export async function getStudentSummary(id: string): Promise<StudentSummary> {
  return data(await apiClient.get(`/api/v1/family/students/${id}/summary`))
}

export type AcademicPath = 'academic-periods' | 'grades' | 'sections' | 'courses' | 'enrollments' | 'teaching-assignments'
export async function listAcademic(path: AcademicPath): Promise<Page<AcademicItem>> {
  return (await apiClient.get<Page<AcademicItem>>(`/api/v1/${path}`)).data
}
export async function createAcademic(path: AcademicPath, input: Record<string, unknown>): Promise<AcademicItem> {
  return data(await apiClient.post(`/api/v1/${path}`, input))
}
export async function updateAcademic(path: AcademicPath, id: string, input: Record<string, unknown>): Promise<AcademicItem> {
  return data(await apiClient.patch(`/api/v1/${path}/${id}`, input))
}
export async function deleteAcademic(path: AcademicPath, id: string): Promise<void> {
  await apiClient.delete(`/api/v1/${path}/${id}`)
}
