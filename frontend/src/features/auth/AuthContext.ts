import { createContext, useContext } from 'react'
import type { AuthUser } from '@/features/auth/types'

export interface AuthContextValue {
  user: AuthUser | null
  isLoading: boolean
  refreshSession: () => Promise<AuthUser | null>
  clearSession: () => void
}

export const AuthContext = createContext<AuthContextValue | null>(null)

export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext)
  if (!value) throw new Error('useAuth debe utilizarse dentro de AuthProvider')
  return value
}
