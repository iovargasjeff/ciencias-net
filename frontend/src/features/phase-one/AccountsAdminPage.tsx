import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { useAuth } from '@/features/auth/AuthContext'
import { createAccount, listAccounts, setAccountActive, setAccountRoles } from './api'
import { getApiError } from '@/lib/api/client'

const operationalRoles = ['gestor_usuarios', 'toe', 'psicologia', 'auxiliar', 'coordinador_academico', 'administrativo', 'docente', 'padre', 'alumno']
const schema = z.object({
  name: z.string().min(1),
  email: z.email(),
  role: z.string().min(1),
  dni: z.string().optional(),
  phone: z.string().optional(),
  last_name: z.string().optional(),
})
type Form = z.infer<typeof schema>

export function AccountsAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()
  const [search, setSearch] = useState('')
  const [message, setMessage] = useState('')
  const accounts = useQuery({ queryKey: ['accounts', search], queryFn: () => listAccounts(search, 'padre,alumno') })
  const roles = operationalRoles
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { name: '', email: '', role: roles[0] } })
  const selectedRole = useWatch({ control: form.control, name: 'role' })
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
        {['docente', 'padre', 'alumno'].includes(selectedRole) && (
          <>
            <label>Apellidos<input {...form.register('last_name')} required /></label>
            <label>DNI<input {...form.register('dni')} required maxLength={15} /></label>
            <label>Telefono<input {...form.register('phone')} required /></label>
          </>
        )}
        {['toe', 'psicologia', 'auxiliar', 'coordinador_academico', 'administrativo', 'gestor_usuarios'].includes(selectedRole) && (
          <p className="form-help">Perfil staff: solo requiere nombre, correo y rol operativo. Superadmin no se crea ni asigna desde esta pantalla.</p>
        )}
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
            <select aria-label={`Rol de ${account.name}`} value={roles.includes(account.roles[0]) ? account.roles[0] : ''} disabled={account.id === user?.id || account.roles.includes('superadmin')} onChange={(event) => changeRoles.mutate({ id: account.id, role: event.target.value })}>
              <option value="" disabled>Rol protegido</option>
              {roles.map((role) => <option key={role}>{role}</option>)}
            </select>
            <button className="button button-secondary" disabled={account.id === user?.id} onClick={() => confirm(`¿Cambiar estado de ${account.name}?`) && activation.mutate({ id: account.id, active: !account.active })}>{account.active ? 'Desactivar' : 'Activar'}</button>
          </div> },
        ]} />
      </div>
    </section>
  )
}
