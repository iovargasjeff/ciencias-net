import { useQuery } from '@tanstack/react-query'
import { useEffect, type PropsWithChildren } from 'react'
import { getSession } from '@/features/auth/api'
import { AuthContext } from '@/features/auth/AuthContext'
import { queryClient } from '@/lib/query/client'

export function AuthProvider({ children }: PropsWithChildren) {
  const session = useQuery({ queryKey: ['auth', 'session'], queryFn: getSession })

  useEffect(() => {
    const clear = () => queryClient.setQueryData(['auth', 'session'], null)
    window.addEventListener('cienciasnet:session-expired', clear)
    return () => window.removeEventListener('cienciasnet:session-expired', clear)
  }, [])

  return (
    <AuthContext.Provider
      value={{
        user: session.data ?? null,
        isLoading: session.isLoading,
        refreshSession: async () => (await session.refetch()).data ?? null,
        clearSession: () => queryClient.setQueryData(['auth', 'session'], null),
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}
