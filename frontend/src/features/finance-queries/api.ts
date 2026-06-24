import { apiClient } from '@/lib/api/client'
import type { AccountStatement, CashReport, Debtor, Receipt, SendRemindersRequest } from './types'

type ApiPage<T> = { data: T[] }
type ApiResource<T> = { data: T }

function pageData<T>(response: { data: ApiPage<T> | T[] }): T[] {
  return Array.isArray(response.data) ? response.data : response.data.data
}

function resourceData<T>(response: { data: ApiResource<T> | T }): T {
  return typeof response.data === 'object' && response.data !== null && 'data' in response.data
    ? (response.data as ApiResource<T>).data
    : response.data
}

function toReceipt(item: Record<string, unknown>): Receipt {
  return {
    id: String(item.id),
    description: String(item.description ?? item.concept_name ?? item.concept ?? 'Obligacion de pago'),
    amount: Number(item.amount ?? item.total_amount ?? item.pending_amount ?? 0),
    status: String(item.status ?? item.payment_status ?? 'pending') as Receipt['status'],
    dueDate: String(item.dueDate ?? item.due_date ?? item.expires_at ?? ''),
    paymentDate: item.paymentDate || item.payment_date ? String(item.paymentDate ?? item.payment_date) : undefined,
  }
}

function toAccountStatement(item: Record<string, unknown>): AccountStatement {
  const receipts = Array.isArray(item.receipts)
    ? item.receipts.map((receipt) => toReceipt(receipt as Record<string, unknown>))
    : []

  const discount = item.earlyPaymentDiscount ?? item.early_payment_discount

  return {
    studentId: String(item.studentId ?? item.student_id ?? item.id),
    studentName: String(item.studentName ?? item.student_name ?? item.name ?? 'Alumno'),
    grade: String(item.grade ?? item.grade_name ?? ''),
    receipts,
    totalDue: Number(item.totalDue ?? item.total_due ?? item.pending_total ?? receipts.reduce((sum, receipt) => sum + (receipt.status === 'paid' ? 0 : receipt.amount), 0)),
    earlyPaymentDiscount: discount && typeof discount === 'object'
      ? {
          eligible: Boolean((discount as Record<string, unknown>).eligible),
          amount: Number((discount as Record<string, unknown>).amount ?? 0),
          deadline: String((discount as Record<string, unknown>).deadline ?? ''),
        }
      : null,
  }
}

function toDebtor(item: Record<string, unknown>): Debtor {
  return {
    studentId: String(item.studentId ?? item.student_id ?? item.id),
    studentName: String(item.studentName ?? item.student_name ?? 'Alumno'),
    guardianName: String(item.guardianName ?? item.guardian_name ?? item.parent_name ?? ''),
    guardianEmail: String(item.guardianEmail ?? item.guardian_email ?? item.parent_email ?? ''),
    overdueAmount: Number(item.overdueAmount ?? item.overdue_amount ?? 0),
    overdueReceiptsCount: Number(item.overdueReceiptsCount ?? item.overdue_receipts_count ?? 0),
    daysOverdue: Number(item.daysOverdue ?? item.days_overdue ?? 0),
  }
}

export const financeQueriesApi = {
  async listAccountStatements(): Promise<AccountStatement[]> {
    const response = await apiClient.get<ApiPage<Record<string, unknown>>>('/api/v1/finance/account-statements')
    return pageData(response).map(toAccountStatement)
  },

  async listDebtors(): Promise<Debtor[]> {
    const response = await apiClient.get<ApiPage<Record<string, unknown>>>('/api/v1/finance/debtors')
    return pageData(response).map(toDebtor)
  },

  async getCashReport(): Promise<CashReport> {
    const response = await apiClient.get<ApiResource<CashReport>>('/api/v1/finance/cash-reports')
    return resourceData(response)
  },

  async sendPaymentReminders(data: SendRemindersRequest): Promise<void> {
    await apiClient.post('/api/v1/finance/payment-reminders', data, {
      headers: { 'Idempotency-Key': `payment-reminders-${Date.now()}` },
    })
  },
}
