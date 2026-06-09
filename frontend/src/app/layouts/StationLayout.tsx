import { Camera, LockKey } from '@phosphor-icons/react'
import { Outlet, useLocation } from 'react-router-dom'

export function StationLayout() {
  const location = useLocation()
  const isCapturePage = location.pathname.endsWith('/captura')

  if (isCapturePage) {
    return <Outlet />
  }

  return (
    <main className="min-h-screen kiosk-bg text-slate-100 flex flex-col justify-between font-sans overflow-x-hidden select-none relative">
      <div className="absolute top-0 left-0 right-0 h-[400px] bg-gradient-to-b from-indigo-950/20 via-transparent to-transparent pointer-events-none z-0" />

      <header className="px-6 py-4 bg-slate-900/40 border-b border-slate-900/60 flex items-center justify-between backdrop-blur-md sticky top-0 z-40 relative">
        <span className="brand brand-light flex items-center gap-2.5 text-lg font-bold text-white">
          <div className="bg-indigo-600/20 p-2 rounded-xl text-indigo-400 border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.15)] animate-float">
            <Camera size={22} weight="duotone" />
          </div>
          Estación CienciasNET
        </span>
        <span className="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-slate-900/60 text-slate-300 border border-slate-800/80 backdrop-blur-sm text-xs font-semibold">
          <LockKey size={13} className="text-indigo-400" /> Sesión técnica limitada
        </span>
      </header>

      <div className="flex-grow flex items-center justify-center relative z-10 w-full py-12">
        <Outlet />
      </div>

      <footer className="px-6 py-4 bg-slate-950/40 border-t border-slate-900/60 flex justify-between items-center text-xs text-slate-450 relative z-10">
        <span>© 2026 Colegio CienciasNET. Todos los derechos reservados.</span>
        <span>Estación Web • Versión 1.0.0</span>
      </footer>
    </main>
  )
}
