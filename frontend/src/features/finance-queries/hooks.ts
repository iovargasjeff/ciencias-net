import { useQuery, useMutation } from '@tanstack/react-query'
import { financeQueriesApi } from './api'
import type { SendRemindersRequest } from './types'

export function useAccountStatements() {
  return useQuery({
    queryKey: ['finance', 'account-statements'],
    queryFn: financeQueriesApi.listAccountStatements,
  })
}

export function useDebtors() {
  return useQuery({
    queryKey: ['finance', 'debtors'],
    queryFn: financeQueriesApi.listDebtors,
  })
}

export function useCashReport() {
  return useQuery({
    queryKey: ['finance', 'cash-report'],
    queryFn: financeQueriesApi.getCashReport,
    retry: false, // For testing Error Boundaries more easily
  })
}

export function useSendPaymentReminders() {
  return useMutation({
    mutationFn: (data: SendRemindersRequest) => financeQueriesApi.sendPaymentReminders(data),
  })
}
