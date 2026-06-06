import { GraduationCap } from '@phosphor-icons/react'
import { Link, Outlet } from 'react-router-dom'

export function PublicLayout() {
  return (
    <div className="app-shell">
      <header className="topbar">
        <Link className="brand" to="/" aria-label="Ir al inicio de CienciasNET">
          <GraduationCap size={30} weight="duotone" aria-hidden />
          <span>CienciasNET</span>
        </Link>
        <Link className="button button-secondary" to="/login">Abrir portal</Link>
      </header>
      <main><Outlet /></main>
    </div>
  )
}
