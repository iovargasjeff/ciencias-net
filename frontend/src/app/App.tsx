import { Route, Routes } from 'react-router-dom'
import { PortalLayout } from '@/app/layouts/PortalLayout'
import { PublicLayout } from '@/app/layouts/PublicLayout'
import { StationLayout } from '@/app/layouts/StationLayout'
import { ContextPage } from '@/features/auth/ContextPage'
import { PermissionRoute, ProtectedRoute, StationRoute } from '@/features/auth/guards'
import { LoginPage } from '@/features/auth/LoginPage'
import { RecoveryPage } from '@/features/auth/RecoveryPage'
import { StationActivationPage } from '@/features/auth/StationActivationPage'
import { StationCapturePage } from '@/features/stations/StationCapturePage'
import { FoundationsPage } from '@/features/home/FoundationsPage'
import { LandingPage } from '@/features/home/LandingPage'
import { AcademicAdminPage } from '@/features/phase-one/AcademicAdminPage'
import { AccountsAdminPage } from '@/features/phase-one/AccountsAdminPage'
import { FamilyAdminPage } from '@/features/phase-one/FamilyAdminPage'
import { FamilyPortalPage } from '@/features/phase-one/FamilyPortalPage'

export function App() {
  return (
    <Routes>
      <Route element={<PublicLayout />}>
        <Route index element={<LandingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/recuperar-contrasena" element={<RecoveryPage />} />
      </Route>
      <Route element={<ProtectedRoute />}>
        <Route path="/seleccionar-contexto" element={<PortalLayout />}><Route index element={<ContextPage />} /></Route>
        <Route path="/portal" element={<PortalLayout />}><Route index element={<FamilyPortalPage />} /></Route>
        <Route element={<PermissionRoute roles={['superadmin', 'gestor_usuarios', 'administrativo', 'coordinador_academico', 'toe', 'psicologia', 'auxiliar']} />}>
          <Route path="/admin" element={<PortalLayout />}>
            <Route index element={<FoundationsPage context="Administración" />} />
            <Route element={<PermissionRoute roles={['superadmin', 'gestor_usuarios']} />}>
              <Route path="cuentas" element={<AccountsAdminPage />} />
              <Route path="familias" element={<FamilyAdminPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin', 'coordinador_academico']} />}>
              <Route path="academia" element={<AcademicAdminPage />} />
            </Route>
          </Route>
        </Route>
      </Route>
      <Route path="/estacion" element={<StationLayout />}>
        <Route index element={<StationActivationPage />} />
        <Route path="activar" element={<StationActivationPage />} />
        <Route element={<StationRoute />}><Route path="captura" element={<StationCapturePage />} /></Route>
      </Route>
    </Routes>
  )
}
