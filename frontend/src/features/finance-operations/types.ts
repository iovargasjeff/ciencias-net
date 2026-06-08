export interface Page<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export type ObligationStatus = 'pending' | 'paid' | 'annulled'
export type PaymentMethod = 'cash' | 'transfer' | 'yape' | 'plin' | 'other'

export interface PaymentObligation {
  id: string
  student_id: string
  student_name: string
  concept_id: string
  concept_name: string
  base_amount: string
  early_payment_amount: string | null
  early_payment_deadline: string | null
  due_date: string
  status: ObligationStatus
  benefit_id: string | null
  created_at: string
  updated_at: string
}

export interface PaymentMovement {
  id: string
  obligation_id: string
  obligation_concept_name?: string
  student_name?: string
  amount_paid: string
  payment_method: PaymentMethod
  reference: string | null
  status: 'completed' | 'refunded' | 'annulled'
  receipt_number: string | null
  created_at: string
  updated_at: string
}
