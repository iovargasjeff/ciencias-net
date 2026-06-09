import type { Page, PaymentConcept, StudentBenefit } from './types'

// Mock Data
let mockConcepts: PaymentConcept[] = [
  { id: '1', code: 'MAT-2026', name: 'Matrícula 2026', amount: '350.00', recurrence: 'single', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
  { id: '2', code: 'PEN-2026', name: 'Pensión Mensual', amount: '450.00', due_day: 5, recurrence: 'monthly', early_payment_discount: '20.00', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
  { id: '3', code: 'MAT-2025', name: 'Matrícula 2025 (Pasada)', amount: '0.00', recurrence: 'single', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
]

let mockBenefits: StudentBenefit[] = [
  { id: '1', student_id: '123e4567-e89b-12d3-a456-426614174000', benefit_type: 'percentage', value: '15.00', active: true, stackable_with_early_payment: false, starts_on: '2026-03-01', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
  { id: '2', student_id: 'a1b2c3d4-e89b-12d3-a456-426614174000', benefit_type: 'waiver', value: null, active: false, stackable_with_early_payment: false, starts_on: '2025-03-01', ends_on: '2025-12-31', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
]

const delay = (ms: number) => new Promise(res => setTimeout(res, ms))

// Payment Concepts API
export async function listPaymentConcepts(search = ''): Promise<Page<PaymentConcept>> {
  await delay(600)
  const filtered = mockConcepts.filter(c => c.name.toLowerCase().includes(search.toLowerCase()) || c.code.toLowerCase().includes(search.toLowerCase()))
  return { data: filtered, meta: { current_page: 1, last_page: 1, per_page: 15, total: filtered.length } }
}

export async function createPaymentConcept(input: Partial<PaymentConcept>): Promise<PaymentConcept> {
  await delay(600)
  const newConcept = { ...input, id: Math.random().toString(36).substring(7), created_at: new Date().toISOString(), updated_at: new Date().toISOString() } as PaymentConcept
  mockConcepts = [newConcept, ...mockConcepts]
  return newConcept
}

export async function updatePaymentConcept(id: string, input: Partial<PaymentConcept>): Promise<PaymentConcept> {
  await delay(600)
  const idx = mockConcepts.findIndex(c => c.id === id)
  if (idx === -1) throw new Error('Payment concept not found')
  mockConcepts[idx] = { ...mockConcepts[idx], ...input, updated_at: new Date().toISOString() }
  return mockConcepts[idx]
}

// Student Benefits API
export async function listStudentBenefits(): Promise<Page<StudentBenefit>> {
  await delay(600)
  return { data: mockBenefits, meta: { current_page: 1, last_page: 1, per_page: 15, total: mockBenefits.length } }
}

export async function createStudentBenefit(input: Partial<StudentBenefit>): Promise<StudentBenefit> {
  await delay(600)
  const newBenefit = { ...input, id: Math.random().toString(36).substring(7), active: true, created_at: new Date().toISOString(), updated_at: new Date().toISOString() } as StudentBenefit
  mockBenefits = [newBenefit, ...mockBenefits]
  return newBenefit
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export async function deactivateStudentBenefit(id: string, _reason: string): Promise<StudentBenefit> {
  await delay(600)
  const idx = mockBenefits.findIndex(b => b.id === id)
  if (idx === -1) throw new Error('Benefit not found')
  mockBenefits[idx] = { ...mockBenefits[idx], active: false, updated_at: new Date().toISOString() }
  return mockBenefits[idx]
}
