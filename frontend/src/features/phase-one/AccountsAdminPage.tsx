import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { useAuth } from '@/features/auth/AuthContext'
import { createAccount, listAccounts, setAccountActive, setAccountRoles } from './api'
import { getApiError } from '@/lib/api/client'

const operationalRoles = ['toe', 'psicologia', 'auxiliar', 'coordinador_academico', 'administrativo', 'docente', 'padre', 'alumno']
const schema = z.object({ name: z.string().min(1), email: z.email(), role: z.string().min(1) })
type Form = z.infer<typeof schema>

export function AccountsAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()
  const [search, setSearch] = useState('')
  const [message, setMessage] = useState('')
  const accounts = useQuery({ queryKey: ['accounts', search], queryFn: () => listAccounts(search, 'padre,alumno') })
  const roles = user?.roles.includes('superadmin') ? ['superadmin', 'gestor_usuarios', ...operationalRoles] : operationalRoles
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { name: '', email: '', role: roles[0] } })
  const invalidate = async () => client.invalidateQueries({ queryKey: ['accounts'] })
  const create = useMutation({
    mutationFn: (values: Form) => createAccount({ name: values.name, email: values.email, roles: [values.role] }),
    onSuccess: async () => { form.reset(); setMessage('Cuenta creada y auditada.'); await invalidate() },
  })
  const changeRoles = useMutation({ mutationFn: ({ id, role }: { id: string; role: string }) => setAccountRoles(id, [role]), onSuccess: invalidate })
  const activation = useMutation({ mutationFn: ({ id, active }: { id: string; active: boolean }) => setAccountActive(id, active), onSuccess: invalidate })

  return (
    <section className="page-stack">
      <header><p className="eyebrow">Identidad y acceso</p><h1>Cuentas y roles</h1><p>Administra cuentas humanas sin alterar su historial.</p></header>
      <form className="panel form-grid" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
        <h2>Nueva cuenta</h2>
        <label>Nombre<input {...form.register('name')} /></label>
        <label>Correo<input type="email" {...form.register('email')} /></label>
        <label>Rol<select {...form.register('role')}>{roles.map((role) => <option key={role}>{role}</option>)}</select></label>
        <button className="button button-primary" disabled={create.isPending}>Crear cuenta</button>
        {message && <p className="form-success">{message}</p>}
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>
      <div className="panel">
        <label>Buscar cuenta<input value={search} onChange={(event) => setSearch(event.target.value)} /></label>
        <DataTable rows={accounts.data?.data} isLoading={accounts.isLoading} error={accounts.error} columns={[
          { label: 'Cuenta', render: (account) => <><strong>{account.name}</strong><small>{account.email}</small></> },
          { label: 'Roles', render: (account) => account.roles.join(', ') },
          { label: 'Estado', render: (account) => account.active ? 'Activa' : 'Inactiva' },
          { label: 'Acciones', render: (account) => <div className="row-actions">
            <select aria-label={`Rol de ${account.name}`} value={account.roles[0] ?? ''} disabled={account.id === user?.id} onChange={(event) => changeRoles.mutate({ id: account.id, role: event.target.value })}>{roles.map((role) => <option key={role}>{role}</option>)}</select>
            <button className="button button-secondary" disabled={account.id === user?.id} onClick={() => confirm(`¿Cambiar estado de ${account.name}?`) && activation.mutate({ id: account.id, active: !account.active })}>{account.active ? 'Desactivar' : 'Activar'}</button>
          </div> },
        ]} />
      </div>
    </section>
  )
}
