import { zodResolver } from '@hookform/resolvers/zod'
import { Devices, Key, ShieldCheck } from '@phosphor-icons/react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { activateStation } from '@/features/stations/api'
import { getApiError } from '@/lib/api/client'

const schema = z.object({
  device_name: z.string().min(1, 'Ingresa el nombre de este dispositivo.'),
  activation_code: z.string().min(1, 'Ingresa el código de activación de un solo uso.'),
})

type ActivationValues = z.infer<typeof schema>

export function StationActivationPage() {
  const navigate = useNavigate()
  const [error, setError] = useState('')

  const form = useForm<ActivationValues>({
    resolver: zodResolver(schema),
    defaultValues: { device_name: '', activation_code: '' },
  })

  const submit = form.handleSubmit(async (values) => {
    setError('')
    try {
      await activateStation(values.activation_code, values.device_name)
      sessionStorage.setItem('cienciasnet.station.context', 'active')
      navigate('/estacion/captura', { replace: true })
    } catch (requestError) {
      setError(getApiError(requestError).message)
    }
  })

  return (
    <section className="glass-panel rounded-3xl p-8 max-w-md w-full mx-auto shadow-2xl relative overflow-hidden text-center flex flex-col items-center" aria-labelledby="activation-title">
      <div className="absolute -top-20 -left-20 w-44 h-44 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -bottom-20 -right-20 w-44 h-44 bg-purple-500/10 rounded-full blur-3xl pointer-events-none" />

      <div className="bg-indigo-600/10 border border-indigo-500/30 p-3.5 rounded-full text-indigo-400 mb-4 shadow-[0_0_20px_rgba(99,102,241,0.15)] animate-float">
        <ShieldCheck size={36} weight="duotone" aria-hidden />
      </div>

      <p className="eyebrow text-[10px] tracking-widest font-bold text-indigo-400 uppercase">
        Contexto Técnico Seguro
      </p>

      <h1 id="activation-title" className="text-2xl font-extrabold text-white tracking-tight mt-2.5 mb-1.5">
        Activar Estación
      </h1>

      <p className="text-xs text-slate-400 mb-6 max-w-sm leading-relaxed">
        Ingresa el código temporal generado desde Biometría y Dispositivos. Esta pantalla no usa códigos simulados.
      </p>

      <form onSubmit={submit} className="auth-form w-full text-left relative z-10 space-y-4">
        <label className="flex flex-col gap-1.5 text-xs font-semibold text-slate-300">
          <span className="flex items-center gap-1.5"><Devices size={14} className="text-indigo-400" /> Nombre del dispositivo</span>
          <input
            type="text"
            placeholder="Ej. Entrada Principal, Tablet Pasadizo"
            className="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm text-white focus:outline-none"
            {...form.register('device_name')}
          />
        </label>
        {form.formState.errors.device_name && (
          <span className="field-error text-xs text-red-400 mt-1 block" role="alert">
            {form.formState.errors.device_name.message}
          </span>
        )}

        <label className="flex flex-col gap-1.5 text-xs font-semibold text-slate-300 mt-3">
          <span className="flex items-center gap-1.5"><Key size={14} className="text-indigo-400" /> Código de activación</span>
          <input
            type="text"
            placeholder="Ingresa el código generado por el administrador"
            className="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm text-white focus:outline-none"
            {...form.register('activation_code')}
          />
        </label>
        {form.formState.errors.activation_code && (
          <span className="field-error text-xs text-red-400 mt-1 block" role="alert">
            {form.formState.errors.activation_code.message}
          </span>
        )}

        {error && <p className="form-error text-xs text-red-400 font-semibold text-center mt-2.5" role="alert">{error}</p>}

        <button
          className="w-full mt-4 flex justify-center items-center gap-2 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold transition shadow-[0_0_20px_rgba(99,102,241,0.25)] hover:shadow-[0_0_25px_rgba(99,102,241,0.45)] active:scale-[0.98] disabled:opacity-50 text-sm"
          type="submit"
          disabled={form.formState.isSubmitting}
        >
          {form.formState.isSubmitting ? 'Verificando...' : 'Activar Dispositivo'}
        </button>
      </form>
    </section>
  )
}
