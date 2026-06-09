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
import { PaymentConceptsPage, StudentBenefitsPage } from '@/features/finance-config'
import { ObligationsPage, PaymentsPage } from '@/features/finance-operations'
import { FamilyAccountStatementPage } from '@/features/finance-queries/FamilyAccountStatementPage'
import { DebtorsReportPage } from '@/features/finance-queries/DebtorsReportPage'
import { CashReportPage } from '@/features/finance-queries/CashReportPage'
import { BiometricAdminPage } from '@/features/biometrics/BiometricAdminPage'
import { StudentAttendancePage } from '@/features/attendance/StudentAttendancePage'
import { PayrollAdminPage } from '@/features/payroll/PayrollAdminPage'
import { AssessmentsPage } from '@/features/assessments/AssessmentsPage'
import { MaterialsAdminPage } from '@/features/materials/MaterialsAdminPage'
import { MaterialsPortalPage } from '@/features/materials/MaterialsPortalPage'
import { SchedulesAdminPage } from '@/features/schedules/SchedulesAdminPage'
import { SchedulesPortalPage } from '@/features/schedules/SchedulesPortalPage'
import { IncidentsAdminPage } from '@/features/incidents/IncidentsAdminPage'
import { IncidentDetailPage } from '@/features/incidents/IncidentDetailPage'
import { FamilyIncidentsPage } from '@/features/incidents/FamilyIncidentsPage'
import { PsychologyAdminPage } from '@/features/psychology/PsychologyAdminPage'

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
        <Route path="/portal" element={<PortalLayout />}>
          <Route index element={<FamilyPortalPage />} />
          <Route path="finanzas/estado-cuenta" element={<FamilyAccountStatementPage />} />
          <Route path="materiales" element={<MaterialsPortalPage />} />
          <Route path="horarios" element={<SchedulesPortalPage />} />
          <Route path="incidencias" element={<FamilyIncidentsPage />} />
        </Route>
        <Route element={<PermissionRoute roles={['superadmin', 'gestor_usuarios', 'administrativo', 'coordinador_academico', 'toe', 'psicologia', 'auxiliar', 'docente']} permissions={['gestionar_dispositivos', 'gestionar_planilla']} />}>
          <Route path="/admin" element={<PortalLayout />}>
            <Route index element={<FoundationsPage context="Administración" />} />
            <Route element={<PermissionRoute roles={['superadmin', 'gestor_usuarios']} />}>
              <Route path="cuentas" element={<AccountsAdminPage />} />
              <Route path="familias" element={<FamilyAdminPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin']} permissions={['gestionar_dispositivos']} />}>
              <Route path="biometria" element={<BiometricAdminPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin', 'coordinador_academico']} />}>
              <Route path="academia" element={<AcademicAdminPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin', 'gestionar_finanzas']} />}>
              <Route path="finanzas/configuracion" element={<PaymentConceptsPage />} />
              <Route path="finanzas/beneficios" element={<StudentBenefitsPage />} />
              <Route path="finanzas/obligaciones" element={<ObligationsPage />} />
              <Route path="finanzas/pagos" element={<PaymentsPage />} />
              <Route path="finanzas/morosos" element={<DebtorsReportPage />} />
              <Route path="finanzas/caja" element={<CashReportPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin', 'auxiliar', 'toe']} />}>
              <Route path="asistencia" element={<StudentAttendancePage />} />
              <Route path="incidencias" element={<IncidentsAdminPage />} />
              <Route path="incidencias/:id" element={<IncidentDetailPage />} />
            </Route>
            <Route element={<PermissionRoute roles={['superadmin']} permissions={['gestionar_planilla']} />}>
              <Route path="planilla" element={<PayrollAdminPage />} />
            </Route>
             <Route element={<PermissionRoute roles={['superadmin', 'coordinador_academico', 'docente']} />}>
              <Route path="evaluaciones" element={<AssessmentsPage />} />
               <Route path="materiales" element={<MaterialsAdminPage />} />
               <Route path="horarios" element={<SchedulesAdminPage />} />
             </Route>
             <Route element={<PermissionRoute roles={['superadmin', 'psicologia']} />}>
               <Route path="psicologia" element={<PsychologyAdminPage />} />
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
