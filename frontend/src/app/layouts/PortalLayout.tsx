import { House, SignOut, UserCircle } from '@phosphor-icons/react'
import { Link, Outlet, useNavigate } from 'react-router-dom'
import { logout } from '@/features/auth/api'
import { useAuth } from '@/features/auth/AuthContext'

export function PortalLayout() {
  const auth = useAuth()
  const navigate = useNavigate()

  async function closeSession() {
    await logout()
    auth.clearSession()
    navigate('/login', { replace: true })
  }

  return (
    <div className="workspace">
      <aside className="sidebar">
        <Link className="brand brand-light" to="/portal"><UserCircle size={30} weight="duotone" /> Portal</Link>
        <nav aria-label="Navegación principal">
          <Link className="nav-link nav-link-active" to="/portal"><House aria-hidden /> Inicio</Link>
        </nav>
        <button className="nav-link nav-button" type="button" onClick={closeSession}><SignOut aria-hidden /> Salir</button>
      </aside>
      <main className="workspace-content"><Outlet /></main>
    </div>
  )
}
