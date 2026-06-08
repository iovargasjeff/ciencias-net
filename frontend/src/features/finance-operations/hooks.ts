import { useQuery } from '@tanstack/react-query'
import { listPaymentObligations, listPaymentMovements } from './api'

export function usePaymentObligations(search: string, status?: string) {
  return useQuery({
    queryKey: ['payment-obligations', search, status],
    queryFn: () => listPaymentObligations(search, status),
  })
}

export function usePaymentMovements(search: string, status?: string) {
  return useQuery({
    queryKey: ['payment-movements', search, status],
    queryFn: () => listPaymentMovements(search, status),
  })
}
