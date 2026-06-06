import { Route, Routes } from 'react-router-dom'
import { PublicLayout } from '@/app/layouts/PublicLayout'
import { PortalLayout } from '@/app/layouts/PortalLayout'
import { StationLayout } from '@/app/layouts/StationLayout'
import { LandingPage } from '@/features/home/LandingPage'
import { FoundationsPage } from '@/features/home/FoundationsPage'
import { ContextPage } from '@/features/auth/ContextPage'
import { LoginPage } from '@/features/auth/LoginPage'
import { RecoveryPage } from '@/features/auth/RecoveryPage'
import { PermissionRoute, ProtectedRoute, StationRoute } from '@/features/auth/guards'
import { StationActivationPage } from '@/features/auth/StationActivationPage'

export function App() {
  return (
    <Routes>
      <Route element={<PublicLayout />}>
        <Route index element={<LandingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/recuperar-contrasena" element={<RecoveryPage />} />
      </Route>
      <Route element={<ProtectedRoute />}>
        <Route path="/seleccionar-contexto" element={<PortalLayout />}>
          <Route index element={<ContextPage />} />
        </Route>
        <Route path="/portal" element={<PortalLayout />}>
          <Route index element={<FoundationsPage context="Portal humano" />} />
        </Route>
        <Route element={<PermissionRoute roles={['superadmin', 'gestor_usuarios', 'administrativo', 'coordinador_academico', 'toe', 'psicologia', 'auxiliar']} />}>
          <Route path="/admin" element={<PortalLayout />}>
            <Route index element={<FoundationsPage context="Administración" />} />
          </Route>
        </Route>
      </Route>
      <Route path="/estacion" element={<StationLayout />}>
        <Route index element={<StationActivationPage />} />
        <Route path="activar" element={<StationActivationPage />} />
        <Route element={<StationRoute />}>
          <Route path="captura" element={<FoundationsPage context="Estación de asistencia" />} />
        </Route>
      </Route>
    </Routes>
  )
}
