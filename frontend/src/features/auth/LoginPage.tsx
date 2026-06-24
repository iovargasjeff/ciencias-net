import { zodResolver } from '@hookform/resolvers/zod'
import { LockKey, SignIn } from '@phosphor-icons/react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { login } from '@/features/auth/api'
import { useAuth } from '@/features/auth/AuthContext'
import { getApiError } from '@/lib/api/client'

const schema = z.object({
  email: z.email('Ingresa un correo válido.'),
  password: z.string().min(1, 'Ingresa tu contraseña.'),
  remember: z.boolean(),
})

type LoginValues = z.infer<typeof schema>

export function LoginPage() {
  const auth = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [error, setError] = useState('')
  const form = useForm<LoginValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', remember: false },
  })

  const submit = form.handleSubmit(async (values) => {
    setError('')
    try {
      const user = await login(values)
      await auth.refreshSession()
      const requested = (location.state as { from?: string } | null)?.from
      const destination = user.roles.includes('superadmin') ? '/admin' : (requested ?? '/seleccionar-contexto')
      navigate(destination, { replace: true })
    } catch (requestError) {
      setError(getApiError(requestError).message)
    }
  })

  return (
    <section className="auth-card" aria-labelledby="login-title">
      <LockKey size={34} weight="duotone" aria-hidden />
      <p className="eyebrow">Acceso humano</p>
      <h1 id="login-title">Ingresa a CienciasNET</h1>
      <p>Utiliza la cuenta entregada por la institución.</p>
      <form onSubmit={submit} className="auth-form">
        <label>Correo<input type="email" autoComplete="email" {...form.register('email')} /></label>
        {form.formState.errors.email && <span className="field-error">{form.formState.errors.email.message}</span>}
        <label>Contraseña<input type="password" autoComplete="current-password" {...form.register('password')} /></label>
        {form.formState.errors.password && <span className="field-error">{form.formState.errors.password.message}</span>}
        <label className="check-field"><input type="checkbox" {...form.register('remember')} /> Mantener sesión</label>
        {error && <p className="form-error" role="alert">{error}</p>}
        <button className="button button-primary" type="submit" disabled={form.formState.isSubmitting}>
          <SignIn aria-hidden /> {form.formState.isSubmitting ? 'Ingresando…' : 'Ingresar'}
        </button>
      </form>
      <Link to="/recuperar-contrasena">Olvidé mi contraseña</Link>
    </section>
  )
}
