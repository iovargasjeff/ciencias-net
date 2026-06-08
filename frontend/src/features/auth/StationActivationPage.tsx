import { zodResolver } from '@hookform/resolvers/zod'
import { Camera, Key, Devices, QrCode, ShieldCheck } from '@phosphor-icons/react'
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
  const [activeTab, setActiveTab] = useState<'manual' | 'qr'>('manual')
  const [isScanning, setIsScanning] = useState(false)
  const [scanSuccess, setScanSuccess] = useState(false)

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

  // Simulated QR Scanning action
  const handleSimulateQRScan = () => {
    setError('')
    setIsScanning(true)
    setTimeout(() => {
      setIsScanning(false)
      setScanSuccess(true)
      
      // Auto-fill values and submit
      form.setValue('device_name', 'Estación QR Auto')
      form.setValue('activation_code', 'QR_CODE_VALID')
      
      setTimeout(async () => {
        try {
          await activateStation('QR_CODE_VALID', 'Estación QR Auto')
          sessionStorage.setItem('cienciasnet.station.context', 'active')
          navigate('/estacion/captura', { replace: true })
        } catch (requestError) {
          setError(getApiError(requestError).message)
          setScanSuccess(false)
        }
      }, 800)
    }, 1500)
  }

  return (
    <section className="glass-panel rounded-3xl p-8 max-w-md w-full mx-auto shadow-2xl relative overflow-hidden text-center flex flex-col items-center" aria-labelledby="activation-title">
      {/* Glow ambient spots inside the card */}
      <div className="absolute -top-20 -left-20 w-44 h-44 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -bottom-20 -right-20 w-44 h-44 bg-purple-500/10 rounded-full blur-3xl pointer-events-none" />

      {/* Security badge icon */}
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
        Configura este dispositivo de asistencia. Requiere un código temporal válido generado desde el panel de administración.
      </p>

      {/* Tabs selectors */}
      <div className="grid grid-cols-2 bg-slate-950/60 p-1 rounded-xl border border-slate-900 w-full mb-6 relative z-10">
        <button
          type="button"
          onClick={() => setActiveTab('manual')}
          className={`flex items-center justify-center gap-1.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200 ${
            activeTab === 'manual'
              ? 'bg-indigo-600 text-white shadow-md'
              : 'text-slate-400 hover:text-slate-200'
          }`}
        >
          <Key size={14} /> Código Manual
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('qr')}
          className={`flex items-center justify-center gap-1.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200 ${
            activeTab === 'qr'
              ? 'bg-indigo-600 text-white shadow-md'
              : 'text-slate-400 hover:text-slate-200'
          }`}
        >
          <QrCode size={14} /> Escanear QR
        </button>
      </div>

      {activeTab === 'manual' ? (
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
              placeholder="Ingresa el código de 6 u 8 caracteres"
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
            {form.formState.isSubmitting ? 'Verificando…' : 'Activar Dispositivo'}
          </button>
        </form>
      ) : (
        <div className="w-full flex flex-col items-center gap-5 relative z-10">
          <div className="w-48 h-48 bg-slate-950/70 border border-slate-800 rounded-2xl relative overflow-hidden flex flex-col items-center justify-center p-4">
            
            {/* Blinking scanning indicator */}
            <div className="absolute top-3 right-3 flex items-center gap-1">
              <span className={`w-2 h-2 rounded-full ${isScanning ? 'bg-red-500 animate-ping' : 'bg-slate-600'}`} />
              <span className="text-[8px] font-bold text-slate-500 tracking-wider uppercase">
                {isScanning ? 'Scanner Active' : 'Standby'}
              </span>
            </div>

            {isScanning ? (
              <>
                {/* Laser scan line anim */}
                <div className="absolute left-0 right-0 h-0.5 bg-red-500 shadow-[0_0_10px_#ef4444] animate-scan-line z-20" />
                <Camera size={32} className="text-indigo-400 animate-pulse mb-2" />
                <span className="text-[10px] text-slate-300 font-semibold animate-pulse">
                  Leyendo código QR...
                </span>
              </>
            ) : scanSuccess ? (
              <div className="text-center flex flex-col items-center">
                <ShieldCheck size={44} className="text-emerald-400 mb-2 animate-bounce" />
                <span className="text-[10px] text-emerald-400 font-bold">
                  Código QR Detectado
                </span>
                <span className="text-[8px] text-slate-400 mt-1 font-mono">
                  QR_CODE_VALID
                </span>
              </div>
            ) : (
              <div className="text-center flex flex-col items-center p-3">
                <QrCode size={48} className="text-slate-500 mb-2" />
                <p className="text-[10px] text-slate-400 leading-normal">
                  Coloca el código QR de la estación frente a la cámara.
                </p>
              </div>
            )}
          </div>

          {error && <p className="form-error text-xs text-red-400 font-semibold text-center" role="alert">{error}</p>}

          <button
            type="button"
            onClick={handleSimulateQRScan}
            disabled={isScanning || scanSuccess}
            className="w-full flex justify-center items-center gap-2 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold transition shadow-[0_0_20px_rgba(99,102,241,0.25)] hover:shadow-[0_0_25px_rgba(99,102,241,0.45)] active:scale-[0.98] disabled:opacity-50 text-sm"
          >
            {isScanning ? 'Escaneando...' : scanSuccess ? 'Activando...' : 'Escanear Código QR'}
          </button>
        </div>
      )}
    </section>
  )
}
