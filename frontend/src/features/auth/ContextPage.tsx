import { ArrowRight, UserCircle } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthContext'

const adminRoles = ['superadmin', 'gestor_usuarios', 'administrativo', 'coordinador_academico', 'toe', 'psicologia', 'auxiliar']

export function ContextPage() {
  const { user } = useAuth()
  const canOpenAdmin = user ? adminRoles.some((role) => user.roles.includes(role)) : false

  return (
    <section className="page-stack">
      <div>
        <p className="eyebrow">Sesión activa</p>
        <h1>Elige tu contexto</h1>
        <p>{user?.name}, cada espacio conserva permisos independientes.</p>
      </div>
      <div className="context-grid">
        <Link className="context-card" to="/portal"><UserCircle size={30} aria-hidden /><strong>Portal personal</strong><ArrowRight aria-hidden /></Link>
        {canOpenAdmin && <Link className="context-card" to="/admin"><UserCircle size={30} aria-hidden /><strong>Administración</strong><ArrowRight aria-hidden /></Link>}
      </div>
    </section>
  )
}
