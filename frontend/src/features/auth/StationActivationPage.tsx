import { Camera } from '@phosphor-icons/react'

export function StationActivationPage() {
  return (
    <section className="auth-card" aria-labelledby="station-title">
      <Camera size={36} weight="duotone" aria-hidden />
      <p className="eyebrow">Contexto separado</p>
      <h1 id="station-title">Activación de estación</h1>
      <p>La activación técnica mediante código se habilitará en su change. Esta vista nunca utiliza una cuenta humana ni permite entrar al portal.</p>
    </section>
  )
}
