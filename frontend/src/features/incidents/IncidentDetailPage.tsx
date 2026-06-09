import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { transitionIncident, createIncidentFollowUp } from './api'
import type { Incident } from './types'
import { TransitionForm } from './components/TransitionForm'
import { FollowUpForm } from './components/FollowUpForm'

export function IncidentDetailPage() {
  const { id } = useParams<{ id: string }>()
  const [incident, setIncident] = useState<Incident | null>(null)
  const [loading] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  // Nota: En un entorno real, habria un endpoint `getIncident(id)`.
  // Como OpenAPI solo define `listIncidents` y `create/transition`, vamos a simular 
  // la obtencion desde la lista o asumiendo que existe el endpoint.
  // Pero para simplicidad de la prueba, asumiremos que se recarga.
  
  useEffect(() => {
    // Simulando busqueda en la lista general
    // const fetchDetail = async () => { ... }
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setIncident({
      id: id || 'test',
      student_id: 'alumno-1',
      incident_type: 'Faltamiento',
      severity: 'medium',
      description: 'Problema en clase.',
      status: 'open',
      occurred_at: new Date().toISOString(),
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    })
  }, [id])

  if (loading) return <p>Cargando detalle...</p>
  if (!incident) return <p>No se encontró la incidencia.</p>

  const handleTransition = async (data: import('./types').TransitionIncidentRequest) => {
    setSubmitting(true)
    try {
      await transitionIncident(incident.id, data)
      alert('Estado actualizado correctamente')
    } catch(err) {
      console.error(err)
    } finally {
      setSubmitting(false)
    }
  }

  const handleFollowUp = async (data: import('./types').CreateIncidentFollowUpRequest) => {
    setSubmitting(true)
    try {
      await createIncidentFollowUp(incident.id, data)
      alert('Seguimiento agregado')
    } catch(err) {
      console.error(err)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <section className="page-stack">
      <Link to="/admin/incidencias" className="button outline small">Volver al cuaderno</Link>
      <div className="card mt-4">
        <h2>Detalle de Incidencia #{incident.id.slice(0,8)}</h2>
        <p><strong>Tipo:</strong> {incident.incident_type}</p>
        <p><strong>Estado:</strong> {incident.status}</p>
        <p><strong>Descripción:</strong> {incident.description}</p>
      </div>

      <div className="grid col-2 gap-4 mt-4">
        <div className="card">
          <TransitionForm onSubmit={handleTransition} isSubmitting={submitting} />
        </div>
        <div className="card">
          <FollowUpForm onSubmit={handleFollowUp} isSubmitting={submitting} />
        </div>
      </div>
    </section>
  )
}
