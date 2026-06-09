import { Books, Fingerprint, House, SignOut, UserCircle, UsersThree, Clock, Coins, Money, FileText, ClipboardText, Brain } from '@phosphor-icons/react'
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthContext'
import { logout } from '@/features/auth/api'

export function PortalLayout() {
  const auth = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const isAdmin = location.pathname.startsWith('/admin')
  const canManageUsers = auth.user?.roles?.some((role) => ['superadmin', 'gestor_usuarios'].includes(role))
  const canManageAcademic = auth.user?.roles?.some((role) => ['superadmin', 'coordinador_academico'].includes(role))
  const canManageFinance = auth.user?.roles?.some((role) => ['superadmin', 'gestionar_finanzas'].includes(role))
  const canManageDevices = auth.user?.roles?.includes('superadmin') || auth.user?.permissions?.includes('gestionar_dispositivos')
  const canSuperviseAttendance = auth.user?.roles?.some((role) => ['superadmin', 'auxiliar', 'toe'].includes(role))
  const canManageIncidents = auth.user?.roles?.some((role) => ['superadmin', 'auxiliar', 'toe'].includes(role))
  const canManagePayroll = auth.user?.roles?.includes('superadmin') || auth.user?.permissions?.includes('gestionar_planilla')
  const canManageAssessments = auth.user?.roles?.some((role) => ['superadmin', 'coordinador_academico', 'docente'].includes(role))
  const canManagePsychology = auth.user?.roles?.some((role) => ['superadmin', 'psicologia'].includes(role))


  async function closeSession() {
    await logout()
    auth.clearSession()
    navigate('/login', { replace: true })
  }

  return (
    <div className="workspace">
      <aside className="sidebar">
        <Link className="brand brand-light" to={isAdmin ? '/admin' : '/portal'}><UserCircle size={30} weight="duotone" /> {isAdmin ? 'Administración' : 'Portal'}</Link>
        <nav aria-label="Navegación principal">
          <Link className="nav-link nav-link-active" to={isAdmin ? '/admin' : '/portal'}><House aria-hidden /> Inicio</Link>
          {!isAdmin && <><Link className="nav-link" to="/portal/finanzas/estado-cuenta"><Money aria-hidden /> Estado de Cuenta</Link><Link className="nav-link" to="/portal/incidencias"><ClipboardText aria-hidden /> Incidencias</Link></>}
          {isAdmin && canManageUsers && <><Link className="nav-link" to="/admin/cuentas"><UserCircle aria-hidden /> Cuentas</Link><Link className="nav-link" to="/admin/familias"><UsersThree aria-hidden /> Familias</Link></>}
          {isAdmin && canManageAcademic && <Link className="nav-link" to="/admin/academia"><Books aria-hidden /> Academia</Link>}
          {isAdmin && canManageFinance && <><Link className="nav-link" to="/admin/finanzas/configuracion"><Money aria-hidden /> Conceptos de Pago</Link><Link className="nav-link" to="/admin/finanzas/beneficios"><Money aria-hidden /> Beneficios</Link><Link className="nav-link" to="/admin/finanzas/obligaciones"><Money aria-hidden /> Obligaciones</Link><Link className="nav-link" to="/admin/finanzas/pagos"><Money aria-hidden /> Pagos</Link><Link className="nav-link" to="/admin/finanzas/morosos"><Money aria-hidden /> Reporte de Morosos</Link><Link className="nav-link" to="/admin/finanzas/caja"><Money aria-hidden /> Cierre de Caja</Link></>}
          {isAdmin && canManageDevices && <Link className="nav-link" to="/admin/biometria"><Fingerprint aria-hidden /> Biometría</Link>}
          {isAdmin && canSuperviseAttendance && <Link className="nav-link" to="/admin/asistencia"><Clock aria-hidden /> Asistencia</Link>}
          {isAdmin && canManageIncidents && <Link className="nav-link" to="/admin/incidencias"><ClipboardText aria-hidden /> Incidencias</Link>}
          {isAdmin && canManagePayroll && <Link className="nav-link" to="/admin/planilla"><Coins aria-hidden /> Planilla</Link>}
          {isAdmin && canManageAssessments && <Link className="nav-link" to="/admin/evaluaciones"><FileText aria-hidden /> Evaluaciones</Link>}
          {isAdmin && canManagePsychology && <Link className="nav-link" to="/admin/psicologia"><Brain aria-hidden /> Psicología</Link>}
        </nav>
        <button className="nav-link nav-button" type="button" onClick={closeSession}><SignOut aria-hidden /> Salir</button>
      </aside>
      <main className="workspace-content"><Outlet /></main>
    </div>
  )
}
