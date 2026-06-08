import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import {
  Camera,
  LockKey,
  Clock,
  CheckCircle,
  XCircle,
  Warning,
  Gear,
  ArrowSquareOut,
  SpinnerGap
} from '@phosphor-icons/react'
import { getStationSession, submitStationCapture } from './api'
import type { Station, StationCaptureResponse } from './types'
import { getApiError } from '@/lib/api/client'

interface LocalCamera {
  deviceId: string
  label: string
  active: boolean
  mode: 'entry' | 'exit' | 'mixed'
}

export function StationCapturePage() {
  const navigate = useNavigate()
  const [station, setStation] = useState<Station | null>(null)
  const [isLoadingSession, setIsLoadingSession] = useState(true)
  const [localCameras, setLocalCameras] = useState<LocalCamera[]>([])
  const [permissionError, setPermissionError] = useState<string | null>(null)
  
  // Kiosk clock
  const [currentTime, setCurrentTime] = useState(new Date())

  // Capture State
  const [captureStatus, setCaptureStatus] = useState<
    'idle' | 'processing' | 'success' | 'review' | 'rejected' | 'timeout' | 'revoked'
  >('idle')
  const [captureResult, setCaptureResult] = useState<StationCaptureResponse | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [showConfig, setShowConfig] = useState(false)

  // References for live streams
  const videoRefs = useRef<Record<string, HTMLVideoElement | null>>({})
  const streamsRef = useRef<Record<string, MediaStream | null>>({})

  // 1. Session verification & revoked status check on mount
  useEffect(() => {
    async function verifySession() {
      try {
        const data = await getStationSession()
        setStation(data)
      } catch (error: unknown) {
        const apiError = getApiError(error)
        const status = axios.isAxiosError(error) ? error.response?.status : undefined
        if (status === 401 || status === 403) {
          setCaptureStatus('revoked')
        } else {
          setErrorMessage(apiError.message)
        }
      } finally {
        setIsLoadingSession(false)
      }
    }
    verifySession()
  }, [])

  // 2. Navigation Safeguards (Block browser back buttons)
  useEffect(() => {
    window.history.pushState(null, '', window.location.href)
    const handlePopState = () => {
      window.history.pushState(null, '', window.location.href)
    }
    window.addEventListener('popstate', handlePopState)
    return () => {
      window.removeEventListener('popstate', handlePopState)
    }
  }, [])

  // 3. Realtime clock updates
  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000)
    return () => clearInterval(timer)
  }, [])

  // 4. Request camera permissions and list all connected devices
  const initCameras = async () => {
    if (!station) return
    try {
      setPermissionError(null)
      // Prompt permissions
      const initialStream = await navigator.mediaDevices.getUserMedia({ video: true })
      // Stop initial track immediately so we can cleanly re-request specific devices
      initialStream.getTracks().forEach((track) => track.stop())

      // Enumerate
      const devices = await navigator.mediaDevices.enumerateDevices()
      const videoDevices = devices.filter((device) => device.kind === 'videoinput')

      if (videoDevices.length === 0) {
        setPermissionError('No se encontraron cámaras de video conectadas a este equipo.')
        return
      }

      // Populate local camera configurations
      const populated: LocalCamera[] = videoDevices.map((device, idx) => ({
        deviceId: device.deviceId,
        label: device.label || `Cámara ${idx + 1}`,
        active: idx === 0,
        mode: 'mixed',
      }))

      setLocalCameras(populated)
    } catch {
      setPermissionError(
        'Permiso de acceso a la cámara rechazado. Por favor, habilita el permiso para usar la estación.'
      )
    }
  }

  useEffect(() => {
    if (station) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      void initCameras()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [station])

  // 5. Manage streams lifecycle based on active cameras selection
  useEffect(() => {
    localCameras.forEach(async (camera) => {
      const activeStream = streamsRef.current[camera.deviceId]
      if (camera.active) {
        if (!activeStream) {
          try {
            const stream = await navigator.mediaDevices.getUserMedia({
              video: { deviceId: { exact: camera.deviceId } },
            })
            streamsRef.current[camera.deviceId] = stream
            
            const videoEl = videoRefs.current[camera.deviceId]
            if (videoEl) {
              videoEl.srcObject = stream
            }
          } catch (err) {
            console.error(`Error al iniciar stream de cámara ${camera.label}:`, err)
          }
        } else {
          const videoEl = videoRefs.current[camera.deviceId]
          if (videoEl && videoEl.srcObject !== activeStream) {
            videoEl.srcObject = activeStream
          }
        }
      } else {
        if (activeStream) {
          activeStream.getTracks().forEach((track) => track.stop())
          streamsRef.current[camera.deviceId] = null
          const videoEl = videoRefs.current[camera.deviceId]
          if (videoEl) {
            videoEl.srcObject = null
          }
        }
      }
    })
  }, [localCameras])

  // Cleanup all streams on unmount
  useEffect(() => {
    const currentStreams = streamsRef.current
    return () => {
      Object.values(currentStreams).forEach((stream) => {
        if (stream) {
          stream.getTracks().forEach((track) => track.stop())
        }
      })
    }
  }, [])

  // Bind video elements on changes
  useEffect(() => {
    localCameras.forEach((camera) => {
      const videoEl = videoRefs.current[camera.deviceId]
      const activeStream = streamsRef.current[camera.deviceId]
      if (videoEl && activeStream && videoEl.srcObject !== activeStream) {
        videoEl.srcObject = activeStream
      }
    })
  }, [localCameras])

  // Toggle local camera active state
  const handleToggleCamera = (deviceId: string) => {
    setLocalCameras((prev) =>
      prev.map((c) => (c.deviceId === deviceId ? { ...c, active: !c.active } : c))
    )
  }

  // Change camera mode
  const handleChangeCameraMode = (deviceId: string, mode: 'entry' | 'exit' | 'mixed') => {
    setLocalCameras((prev) =>
      prev.map((c) => (c.deviceId === deviceId ? { ...c, mode } : c))
    )
  }

  // 6. Technical capture flow & Submission (with Idempotency Key)
  const handleCapture = async (camera: LocalCamera) => {
    const video = videoRefs.current[camera.deviceId]
    const stream = streamsRef.current[camera.deviceId]
    if (!video || !stream) return

    setCaptureStatus('processing')
    setErrorMessage(null)

    try {
      // Draw frame to canvas
      const canvas = document.createElement('canvas')
      canvas.width = video.videoWidth || 640
      canvas.height = video.videoHeight || 480
      const ctx = canvas.getContext('2d')
      
      if (!ctx) {
        throw new Error('No se pudo inicializar el contexto 2D del canvas.')
      }

      ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

      canvas.toBlob(async (blob) => {
        if (!blob) {
          setCaptureStatus('idle')
          setErrorMessage('Error al capturar la imagen de la cámara.')
          return
        }

        const idempotencyKey = crypto.randomUUID()
        const capturedAt = new Date().toISOString()
        
        const apiPromise = submitStationCapture(blob, camera.deviceId, capturedAt, idempotencyKey)
        const timeoutPromise = new Promise<never>((_, reject) =>
          setTimeout(() => reject(new Error('timeout')), 5000)
        )

        try {
          const result = await Promise.race([apiPromise, timeoutPromise])
          setCaptureResult(result)

          // Map outcome status
          if (result.outcome === 'accepted') {
            setCaptureStatus('success')
            // Auto-reset after 3s
            setTimeout(() => {
              setCaptureStatus('idle')
              setCaptureResult(null)
            }, 3000)
          } else if (result.outcome === 'review') {
            setCaptureStatus('review')
            setTimeout(() => {
              setCaptureStatus('idle')
              setCaptureResult(null)
            }, 4000)
          } else {
            setCaptureStatus('rejected')
            setTimeout(() => {
              setCaptureStatus('idle')
              setCaptureResult(null)
            }, 4000)
          }
        } catch (err: unknown) {
          const status = axios.isAxiosError(err) ? err.response?.status : undefined
          if (err instanceof Error && err.message === 'timeout') {
            setCaptureStatus('timeout')
            setTimeout(() => {
              setCaptureStatus('idle')
            }, 4000)
          } else if (status === 401 || status === 403) {
            setCaptureStatus('revoked')
          } else {
            const apiError = getApiError(err)
            setErrorMessage(apiError.message)
            setCaptureStatus('idle')
          }
        }
      }, 'image/jpeg')
    } catch {
      setCaptureStatus('idle')
      setErrorMessage('Ocurrió un error inesperado al procesar la captura.')
    }
  }

  // 7. Explicit deactivate (clear context)
  const handleDeactivate = () => {
    sessionStorage.removeItem('cienciasnet.station.context')
    navigate('/estacion/activar', { replace: true })
  }

  if (isLoadingSession) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen kiosk-bg text-white p-6 relative">
        <SpinnerGap size={48} className="spin text-indigo-500 mb-4 animate-spin" />
        <p className="text-lg font-semibold tracking-wide">Validando sesión técnica de estación...</p>
      </div>
    )
  }

  // Render Revoked State
  if (captureStatus === 'revoked') {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen kiosk-bg text-white p-6 relative">
        <div className="glass-panel border border-red-500/30 rounded-2xl p-8 max-w-md text-center shadow-2xl relative overflow-hidden">
          <div className="absolute -top-10 -left-10 w-28 h-28 bg-red-500/10 rounded-full blur-2xl pointer-events-none" />
          <XCircle size={64} className="text-red-500 mx-auto mb-4 animate-pulse" />
          <h1 className="text-2xl font-bold tracking-tight mb-2">Estación Revocada</h1>
          <p className="text-slate-400 mb-6 leading-relaxed text-sm">
            La sesión técnica para esta estación ha sido revocada o ha expirado. Por favor, comunícate con un administrador para reactivar el dispositivo.
          </p>
          <button
            onClick={handleDeactivate}
            className="w-full py-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold transition shadow-[0_0_20px_rgba(239,68,68,0.25)] active:scale-[0.98] text-sm"
          >
            Volver a Activación
          </button>
        </div>
      </div>
    )
  }

  const activeCameras = localCameras.filter((c) => c.active)

  return (
    <div className="min-h-screen kiosk-bg text-slate-100 flex flex-col justify-between font-sans overflow-x-hidden select-none relative">
      <div className="absolute top-0 left-0 right-0 h-[300px] bg-gradient-to-b from-indigo-950/20 to-transparent pointer-events-none z-0" />

      {/* Kiosk Header */}
      <header className="px-6 py-4 bg-slate-900/40 border-b border-slate-900/60 flex items-center justify-between backdrop-blur-md sticky top-0 z-40 relative z-10">
        <div className="flex items-center gap-3">
          <div className="bg-indigo-600/20 p-2 rounded-xl text-indigo-400 border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.15)] animate-float">
            <Camera size={22} weight="duotone" />
          </div>
          <div>
            <h2 className="font-bold tracking-tight text-white text-base leading-none">
              {station?.name || 'Estación de Asistencia'}
            </h2>
            <p className="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1 font-mono uppercase tracking-wider">
              <LockKey size={10} className="text-indigo-400" /> {station?.location || 'Ubicación remota'} • Sesión técnica
            </p>
          </div>
        </div>

        <div className="flex items-center gap-6">
          {/* Current Time Display */}
          <div className="flex items-center gap-2 font-mono font-bold text-lg text-slate-200 bg-slate-950/60 px-4 py-2 rounded-xl border border-slate-900 backdrop-blur-sm shadow-inner">
            <Clock size={18} className="text-indigo-400" />
            <span>
              {currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
            </span>
          </div>

          <div className="flex items-center gap-2.5">
            <button
              onClick={() => setShowConfig(!showConfig)}
              className={`p-2.5 rounded-xl border transition backdrop-blur-sm cursor-pointer ${
                showConfig 
                  ? 'bg-indigo-600 border-indigo-500/50 text-white shadow-[0_0_15px_rgba(99,102,241,0.25)]' 
                  : 'bg-slate-900/60 border-slate-800/85 text-slate-300 hover:bg-slate-800/60 hover:text-white'
              }`}
              title="Configuración de cámaras"
              aria-label="Configurar cámaras"
            >
              <Gear size={20} />
            </button>
            <button
              onClick={handleDeactivate}
              className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-red-950/30 hover:bg-red-900/30 text-red-400 border border-red-500/25 transition text-xs font-bold shadow-[0_0_15px_rgba(239,68,68,0.05)]"
            >
              <ArrowSquareOut size={15} /> Desactivar
            </button>
          </div>
        </div>
      </header>

      {/* Main Kiosk Content */}
      <main className="flex-grow p-6 flex flex-col justify-center max-w-7xl mx-auto w-full relative z-10 py-10">
        {permissionError ? (
          <div className="glass-panel rounded-3xl p-8 text-center max-w-md mx-auto shadow-2xl relative overflow-hidden flex flex-col items-center">
            <div className="absolute -top-10 -left-10 w-28 h-28 bg-yellow-500/10 rounded-full blur-2xl pointer-events-none" />
            <div className="bg-yellow-500/10 border border-yellow-500/30 p-4 rounded-full text-yellow-450 mb-5 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
              <Warning size={40} weight="duotone" />
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Permiso de Cámara Requerido</h3>
            <p className="text-slate-400 text-xs mb-6 leading-relaxed">{permissionError}</p>
            <button
              onClick={() => { void initCameras() }}
              className="w-full flex justify-center items-center gap-2 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold transition shadow-[0_0_20px_rgba(99,102,241,0.25)] hover:shadow-[0_0_25px_rgba(99,102,241,0.45)] active:scale-[0.98] text-xs"
            >
              Reintentar Permiso
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            
            {/* Cameras feed section */}
            <div className={`lg:col-span-3 grid gap-6 ${activeCameras.length > 1 ? 'grid-cols-2' : 'grid-cols-1'}`}>
              {activeCameras.length === 0 ? (
                <div className="glass-panel rounded-3xl p-16 text-center text-slate-400 flex flex-col items-center">
                  <Camera size={56} className="mb-4 text-slate-600 animate-pulse" />
                  <p className="text-base font-semibold text-slate-350">No hay cámaras seleccionadas</p>
                  <p className="text-xs text-slate-500 mt-2">Usa el botón de engranaje arriba para activar cámaras.</p>
                </div>
              ) : (
                activeCameras.map((camera) => (
                  <div
                    key={camera.deviceId}
                    className="glass-panel rounded-3xl overflow-hidden shadow-2xl flex flex-col aspect-video group relative border border-slate-800/80"
                  >
                    {/* Live stream */}
                    <div className="relative flex-1 bg-slate-950 overflow-hidden flex items-center justify-center">
                      <video
                        ref={(el) => { videoRefs.current[camera.deviceId] = el }}
                        autoPlay
                        playsInline
                        muted
                        className="w-full h-full object-cover transform -scale-x-100"
                      />

                      {/* Pulsing "Esperando rostro..." Badge */}
                      <div className="absolute top-4 right-4 flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-950/80 border border-slate-800/80 backdrop-blur-md">
                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-550 animate-ping" />
                        <span className="text-[9px] font-bold text-indigo-400 uppercase tracking-widest font-mono">
                          Esperando rostro...
                        </span>
                      </div>

                      {/* Oval face guide overlay */}
                      <div className="absolute inset-0 pointer-events-none flex items-center justify-center animate-pulse">
                        <div className="w-[45%] aspect-[3/4] border-2 border-dashed border-indigo-500/50 rounded-[50%] bg-indigo-500/5 shadow-[0_0_80px_rgba(99,102,241,0.1)] flex items-center justify-center transition group-hover:scale-105">
                          <span className="text-[9px] uppercase font-bold tracking-widest text-indigo-400 bg-slate-955/90 px-2.5 py-1 rounded-full border border-indigo-500/25">
                            Alinea tu rostro
                          </span>
                        </div>
                      </div>

                      {/* Mode Badge & Details */}
                      <div className="absolute top-4 left-4 flex gap-2">
                        <span className="px-2.5 py-1 text-[10px] font-extrabold rounded-lg bg-indigo-600 text-white shadow-lg uppercase tracking-wider border border-indigo-500/20">
                          {camera.mode === 'entry' ? 'INGRESO' : camera.mode === 'exit' ? 'SALIDA' : 'MIXTO'}
                        </span>
                        <span className="px-2.5 py-1 text-[10px] font-semibold rounded-lg bg-slate-900/90 text-slate-350 border border-slate-800 backdrop-blur-md">
                          {camera.label}
                        </span>
                      </div>
                    </div>

                    {/* Technical Capture trigger bar */}
                    <div className="px-5 py-3.5 bg-slate-900/40 border-t border-slate-800/80 flex items-center justify-between backdrop-blur-md">
                      <span className="text-[10px] text-slate-500 font-mono tracking-tight">
                        ID: {camera.deviceId.substring(0, 8)}...
                      </span>
                      <button
                        onClick={() => handleCapture(camera)}
                        className="flex items-center gap-1.5 px-4.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition active:scale-[0.98] shadow-md shadow-indigo-600/25 border border-indigo-500/20 cursor-pointer"
                      >
                        <Camera size={14} /> Registrar Asistencia
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>

            {/* Sidebar Details / Camera Config Panel */}
            <div className="lg:col-span-1 space-y-6">
              
              {/* Configuration panel */}
              <div className="glass-panel rounded-3xl p-5 shadow-2xl relative overflow-hidden">
                <div className="absolute -top-10 -left-10 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none" />
                <h3 className="text-xs font-bold tracking-widest text-indigo-400 mb-4 flex items-center gap-2 border-b border-slate-800/60 pb-2.5 uppercase">
                  <Gear size={15} /> Configuración de Cámaras
                </h3>

                <div className="space-y-4">
                  {localCameras.map((camera) => (
                    <div key={camera.deviceId} className="space-y-2.5 border-b border-slate-800/40 pb-3.5 last:border-b-0 last:pb-0">
                      <div className="flex items-start justify-between">
                        <label className="flex items-start gap-2.5 cursor-pointer">
                          <input
                            type="checkbox"
                            checked={camera.active}
                            onChange={() => handleToggleCamera(camera.deviceId)}
                            className="mt-0.5 w-4.5 h-4.5 rounded border-slate-800 text-indigo-600 focus:ring-indigo-500/30 accent-indigo-600 cursor-pointer"
                            aria-label={`Activar ${camera.label}`}
                          />
                          <div>
                            <span className="text-xs font-bold text-slate-200 block leading-snug">
                              {camera.label}
                            </span>
                            <span className="text-[9px] text-slate-400 block mt-0.5 font-mono">
                              ID: {camera.deviceId.substring(0, 12)}
                            </span>
                          </div>
                        </label>
                      </div>

                      {camera.active && (
                        <div className="pl-7 space-y-1">
                          <label className="text-[10px] text-slate-400 block font-semibold">Modo de cámara:</label>
                          <select
                            value={camera.mode}
                            onChange={(e) =>
                              handleChangeCameraMode(camera.deviceId, e.target.value as 'entry' | 'exit' | 'mixed')
                            }
                            className="w-full text-xs glass-input rounded-xl px-2.5 py-2 cursor-pointer font-sans"
                            aria-label={`Modo de ${camera.label}`}
                          >
                            <option value="entry">Entrada únicamente</option>
                            <option value="exit">Salida únicamente</option>
                            <option value="mixed">Bidireccional / Mixto</option>
                          </select>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Attendance guidelines */}
              <div className="glass-panel rounded-3xl p-5 text-[11px] text-slate-400 space-y-2.5 shadow-xl">
                <h4 className="font-bold text-slate-200 uppercase tracking-wider text-[10px] text-indigo-400">Guía del Estudiante:</h4>
                <ol className="list-decimal list-inside space-y-2 leading-relaxed">
                  <li>Ubícate de frente a la cámara seleccionada.</li>
                  <li>Asegúrate de que tu rostro quede dentro del recuadro.</li>
                  <li>Evita usar gorras, lentes oscuros o accesorios.</li>
                  <li>Espera a ver el mensaje de confirmación verde.</li>
                </ol>
              </div>
            </div>

          </div>
        )}

        {errorMessage && (
          <div className="mt-6 bg-red-500/10 border border-red-500/30 text-red-400 p-3.5 rounded-2xl text-center text-sm font-semibold max-w-xl mx-auto shadow-md">
            {errorMessage}
          </div>
        )}
      </main>

      {/* Outcome Modal / Overlay */}
      {captureStatus !== 'idle' && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 transition-all duration-300">
          
          {/* Processing overlay */}
          {captureStatus === 'processing' && (
            <div className="bg-slate-900/80 border border-slate-800/80 rounded-3xl p-8 max-w-sm text-center shadow-2xl backdrop-blur-xl animate-fade-in flex flex-col items-center">
              <SpinnerGap size={48} className="spin text-indigo-500 mb-4 animate-spin" />
              <h3 className="text-lg font-bold text-white mb-2">Procesando Identificación</h3>
              <p className="text-slate-400 text-sm">
                Analizando el registro biométrico y confirmando marca de asistencia. Un momento...
              </p>
            </div>
          )}

          {/* Success screen (Green) */}
          {captureStatus === 'success' && captureResult && (
            <div className="bg-emerald-950/60 border border-emerald-500/35 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl backdrop-blur-xl animate-scale-up flex flex-col items-center relative overflow-hidden">
              <div className="absolute -top-10 -left-10 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none" />
              <div className="bg-emerald-500/20 border border-emerald-500/40 p-4 rounded-full text-emerald-450 mb-4 shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                <CheckCircle size={48} weight="fill" />
              </div>
              <h3 className="text-2xl font-extrabold text-emerald-400 mb-1 tracking-tight">
                ¡Asistencia Registrada!
              </h3>
              <p className="text-xs font-bold uppercase tracking-widest text-emerald-500/80">
                Registro de Asistencia exitoso
              </p>
              
              <div className="my-6 bg-slate-950/60 border border-emerald-500/25 py-4 px-6 rounded-2xl w-full backdrop-blur-md">
                <span className="text-[10px] uppercase font-bold text-emerald-450 block tracking-wider mb-1 font-mono">
                  Estudiante identificado
                </span>
                <strong className="text-lg text-white font-bold block truncate">
                  {captureResult.student_name || 'Estudiante'}
                </strong>
                <span className="text-xs text-emerald-300/80 block mt-2 font-mono">
                  {new Date(captureResult.occurred_at).toLocaleTimeString()}
                </span>
              </div>

              <p className="text-[10px] text-slate-400">
                Esta ventana se cerrará automáticamente en 3 segundos.
              </p>
            </div>
          )}

          {/* Review screen (Yellow) */}
          {captureStatus === 'review' && captureResult && (
            <div className="bg-amber-950/60 border border-amber-500/30 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl backdrop-blur-xl animate-scale-up flex flex-col items-center relative overflow-hidden">
              <div className="absolute -top-10 -left-10 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none" />
              <div className="bg-amber-500/20 border border-amber-500/40 p-4 rounded-full text-amber-450 mb-4 shadow-[0_0_20px_rgba(245,158,11,0.2)]">
                <Warning size={48} weight="fill" />
              </div>
              <h3 className="text-2xl font-extrabold text-amber-400 mb-1 tracking-tight">
                Asistencia en Revisión
              </h3>
              <p className="text-xs font-bold uppercase tracking-widest text-amber-550/80">
                Rostro dudoso, un auxiliar debe revisar
              </p>

              <div className="my-6 bg-slate-950/60 border border-amber-500/25 py-4 px-6 rounded-2xl w-full backdrop-blur-md">
                <p className="text-slate-300 text-sm leading-relaxed mb-3">
                  Tu registro facial está siendo validado por el personal de asistencia.
                </p>
                <span className="text-[10px] uppercase font-bold text-amber-450 block tracking-wider font-mono">
                  Hora de registro
                </span>
                <span className="text-sm text-white font-mono block">
                  {new Date(captureResult.occurred_at).toLocaleTimeString()}
                </span>
              </div>

              <p className="text-[10px] text-slate-400">
                Esta ventana se cerrará automáticamente en 4 segundos.
              </p>
            </div>
          )}

          {/* Reject screen (Red) */}
          {captureStatus === 'rejected' && (
            <div className="bg-red-950/60 border border-red-500/30 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl backdrop-blur-xl animate-scale-up flex flex-col items-center relative overflow-hidden">
              <div className="absolute -top-10 -left-10 w-28 h-28 bg-red-500/10 rounded-full blur-2xl pointer-events-none" />
              <div className="bg-red-500/20 border border-red-500/40 p-4 rounded-full text-red-450 mb-4 shadow-[0_0_20px_rgba(239,68,68,0.2)]">
                <XCircle size={48} weight="fill" />
              </div>
              <h3 className="text-2xl font-extrabold text-red-400 mb-1 tracking-tight">
                Asistencia Rechazada
              </h3>
              <p className="text-xs font-bold uppercase tracking-widest text-red-500/80">
                Rostro no Reconocido
              </p>

              <div className="my-6 bg-slate-950/60 border border-red-500/25 py-4 px-6 rounded-2xl w-full backdrop-blur-md">
                <p className="text-slate-300 text-sm leading-relaxed">
                  No reconocido o acceso denegado. Por favor, intenta de nuevo alineándote bien o solicita registro manual con tu auxiliar.
                </p>
              </div>

              <p className="text-[10px] text-slate-400">
                Esta ventana se cerrará automáticamente en 4 segundos.
              </p>
            </div>
          )}

          {/* Timeout screen (Gray) */}
          {captureStatus === 'timeout' && (
            <div className="bg-slate-900/80 border border-slate-800/80 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl backdrop-blur-xl animate-scale-up flex flex-col items-center relative overflow-hidden">
              <div className="absolute -top-10 -left-10 w-28 h-28 bg-slate-500/10 rounded-full blur-2xl pointer-events-none" />
              <div className="bg-slate-800 border border-slate-700/85 p-4 rounded-full text-slate-400 mb-4 shadow-[0_0_20px_rgba(148,163,184,0.2)]">
                <Warning size={48} />
              </div>
              <h3 className="text-2xl font-extrabold text-slate-200 mb-1 tracking-tight">
                Tiempo de Espera Agotado
              </h3>
              <p className="text-xs font-bold uppercase tracking-widest text-slate-450/80">
                Problema de conexión, intente de nuevo
              </p>

              <div className="my-6 bg-slate-950/60 border border-slate-800/25 py-4 px-6 rounded-2xl w-full backdrop-blur-md">
                <p className="text-slate-300 text-sm leading-relaxed">
                  El servidor de identificación tardó demasiado en responder (límite 5s). Intenta capturar nuevamente o regístrate en portería.
                </p>
              </div>

              <p className="text-[10px] text-slate-400">
                Esta ventana se cerrará automáticamente en 4 segundos.
              </p>
            </div>
          )}

        </div>
      )}

      {/* Kiosk Footer */}
      <footer className="px-6 py-4 bg-slate-950/80 border-t border-slate-900/60 flex justify-between items-center text-xs text-slate-450 relative z-10">
        <span>© 2026 Colegio CienciasNET. Todos los derechos reservados.</span>
        <span>Estación Web • Versión 1.0.0</span>
      </footer>

    </div>
  )
}
