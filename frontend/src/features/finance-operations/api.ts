import type { Page, PaymentObligation, PaymentMovement, PaymentMethod } from './types'

// Mock Data
const mockObligations: PaymentObligation[] = [
  { id: '1', student_id: 'a1', student_name: 'Juan Perez', concept_id: 'c1', concept_name: 'Pensión Marzo', base_amount: '480.00', early_payment_amount: '450.00', early_payment_deadline: '2026-03-31', due_date: '2026-04-05', status: 'pending', benefit_id: null, created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
  { id: '2', student_id: 'a2', student_name: 'Maria Gomez', concept_id: 'c1', concept_name: 'Pensión Marzo', base_amount: '480.00', early_payment_amount: null, early_payment_deadline: null, due_date: '2026-04-05', status: 'paid', benefit_id: null, created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
  { id: '3', student_id: 'a1', student_name: 'Juan Perez', concept_id: 'c2', concept_name: 'Matrícula 2026', base_amount: '350.00', early_payment_amount: null, early_payment_deadline: null, due_date: '2026-02-15', status: 'annulled', benefit_id: null, created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
]

const mockMovements: PaymentMovement[] = [
  { id: 'm1', obligation_id: '2', obligation_concept_name: 'Pensión Marzo', student_name: 'Maria Gomez', amount_paid: '480.00', payment_method: 'transfer', reference: 'REF-00123', status: 'completed', receipt_number: 'REC-00001', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
]

const delay = (ms: number) => new Promise(res => setTimeout(res, ms))

export async function listPaymentObligations(search = '', status?: string): Promise<Page<PaymentObligation>> {
  await delay(600)
  let filtered = mockObligations
  if (search) {
    filtered = filtered.filter(o => o.student_name.toLowerCase().includes(search.toLowerCase()) || o.concept_name.toLowerCase().includes(search.toLowerCase()))
  }
  if (status) {
    filtered = filtered.filter(o => o.status === status)
  }
  return { data: filtered, meta: { current_page: 1, last_page: 1, per_page: 15, total: filtered.length } }
}

export async function generatePaymentObligations(input: { concept_id: string; student_ids: string[]; apply_benefits: boolean }): Promise<void> {
  await delay(800)
  input.student_ids.forEach((id) => {
    mockObligations.unshift({
      id: Math.random().toString(36).substring(7),
      student_id: id,
      student_name: `Estudiante ${id}`, // Mock
      concept_id: input.concept_id,
      concept_name: 'Nuevo Concepto',
      base_amount: '480.00',
      early_payment_amount: '450.00',
      early_payment_deadline: new Date(new Date().setDate(new Date().getDate() + 15)).toISOString().split('T')[0],
      due_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0],
      status: 'pending',
      benefit_id: null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    })
  })
}

export async function adjustPaymentObligation(id: string, input: { reason: string; due_date?: string; early_payment_deadline?: string; base_amount?: string }): Promise<PaymentObligation> {
  await delay(600)
  const idx = mockObligations.findIndex(o => o.id === id)
  if (idx === -1) throw new Error('Obligación no encontrada')
  if (mockObligations[idx].status !== 'pending') throw new Error('Solo se pueden ajustar deudas pendientes')
  
  mockObligations[idx] = { ...mockObligations[idx], ...input, updated_at: new Date().toISOString() }
  return mockObligations[idx]
}

export async function listPaymentMovements(search = '', status?: string): Promise<Page<PaymentMovement>> {
  await delay(600)
  let filtered = mockMovements
  if (search) {
    filtered = filtered.filter(m => m.student_name?.toLowerCase().includes(search.toLowerCase()) || m.obligation_concept_name?.toLowerCase().includes(search.toLowerCase()) || m.receipt_number?.toLowerCase().includes(search.toLowerCase()))
  }
  if (status) {
    filtered = filtered.filter(m => m.status === status)
  }
  return { data: filtered, meta: { current_page: 1, last_page: 1, per_page: 15, total: filtered.length } }
}

export async function createPaymentMovement(input: { obligation_id: string; payment_method: string; reference?: string }): Promise<PaymentMovement> {
  await delay(800)
  const oblIdx = mockObligations.findIndex(o => o.id === input.obligation_id)
  if (oblIdx === -1) throw new Error('Obligación no encontrada')
  const obligation = mockObligations[oblIdx]
  if (obligation.status !== 'pending') throw new Error('La obligación ya no está pendiente')

  // Calcular el monto exacto exigible hoy
  const today = new Date().toISOString().split('T')[0]
  const amountToPay = (obligation.early_payment_deadline && today <= obligation.early_payment_deadline && obligation.early_payment_amount) 
    ? obligation.early_payment_amount 
    : obligation.base_amount

  const movement: PaymentMovement = {
    id: Math.random().toString(36).substring(7),
    obligation_id: obligation.id,
    obligation_concept_name: obligation.concept_name,
    student_name: obligation.student_name,
    amount_paid: amountToPay,
    payment_method: input.payment_method as PaymentMethod,
    reference: input.reference || null,
    status: 'completed',
    receipt_number: `REC-${Math.floor(Math.random() * 10000).toString().padStart(5, '0')}`,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  }

  mockMovements.unshift(movement)
  mockObligations[oblIdx].status = 'paid'
  
  return movement
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export async function annulPaymentMovement(id: string, _reason: string): Promise<PaymentMovement> {
  await delay(600)
  const idx = mockMovements.findIndex(m => m.id === id)
  if (idx === -1) throw new Error('Movimiento no encontrado')
  if (mockMovements[idx].status !== 'completed') throw new Error('El movimiento no puede ser anulado')
  
  mockMovements[idx].status = 'annulled'
  
  // Liberar la deuda
  const oblIdx = mockObligations.findIndex(o => o.id === mockMovements[idx].obligation_id)
  if (oblIdx !== -1) {
    mockObligations[oblIdx].status = 'pending'
  }
  
  return mockMovements[idx]
}
