import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthContext'
import { isStationContext } from '@/features/auth/stationContext'
import { OperationalState } from '@/components/shared/OperationalState'

export function ProtectedRoute() {
  const auth = useAuth()
  const location = useLocation()

  if (isStationContext()) return <Navigate to="/estacion/captura" replace />
  if (auth.isLoading) return <OperationalState state="loading" title="Validando sesión" message="Comprobando acceso seguro." />
  if (!auth.user) return <Navigate to="/login" replace state={{ from: location.pathname }} />

  return <Outlet />
}

export function PermissionRoute({ roles, permissions }: { roles?: string[]; permissions?: string[] }) {
  const { user } = useAuth()

  if (!user) {
    return <OperationalState state="forbidden" title="Sin permiso" message="Tu cuenta no puede abrir este espacio." />
  }

  const hasRole = roles ? roles.some((role) => user.roles.includes(role)) : false
  const hasPermission = permissions ? permissions.some((perm) => user.permissions.includes(perm)) : false

  if (!hasRole && !hasPermission) {
    return <OperationalState state="forbidden" title="Sin permiso" message="Tu cuenta no puede abrir este espacio." />
  }

  return <Outlet />
}

export function StationRoute() {
  if (!isStationContext()) return <Navigate to="/estacion/activar" replace />
  return <Outlet />
}
