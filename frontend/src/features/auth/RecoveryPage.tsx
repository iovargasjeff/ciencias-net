import { zodResolver } from '@hookform/resolvers/zod'
import { EnvelopeSimple } from '@phosphor-icons/react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { requestPasswordRecovery } from '@/features/auth/api'
import { getApiError } from '@/lib/api/client'

const schema = z.object({ email: z.email('Ingresa un correo válido.') })
type RecoveryValues = z.infer<typeof schema>

export function RecoveryPage() {
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const form = useForm<RecoveryValues>({ resolver: zodResolver(schema), defaultValues: { email: '' } })

  const submit = form.handleSubmit(async ({ email }) => {
    setError('')
    try {
      setMessage(await requestPasswordRecovery(email))
    } catch (requestError) {
      setError(getApiError(requestError).message)
    }
  })

  return (
    <section className="auth-card" aria-labelledby="recovery-title">
      <EnvelopeSimple size={34} weight="duotone" aria-hidden />
      <p className="eyebrow">Recuperación</p>
      <h1 id="recovery-title">Recupera tu acceso</h1>
      <p>Enviaremos instrucciones si el correo pertenece a una cuenta registrada.</p>
      <form onSubmit={submit} className="auth-form">
        <label>Correo<input type="email" autoComplete="email" {...form.register('email')} /></label>
        {form.formState.errors.email && <span className="field-error">{form.formState.errors.email.message}</span>}
        {error && <p className="form-error" role="alert">{error}</p>}
        {message && <p className="form-success" role="status">{message}</p>}
        <button className="button button-primary" type="submit" disabled={form.formState.isSubmitting}>
          Enviar instrucciones
        </button>
      </form>
    </section>
  )
}
