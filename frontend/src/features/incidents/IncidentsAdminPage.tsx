import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listIncidents, createIncident } from './api'
import type { Incident } from './types'
import { IncidentForm } from './components/IncidentForm'
import { DataTable } from '@/components/shared/DataTable'
import { OperationalState } from '@/components/shared/OperationalState'
import { getApiError } from '@/lib/api/client'

export function IncidentsAdminPage() {
  const [incidents, setIncidents] = useState<Incident[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState('')

  const fetchIncidents = async () => {
    setLoading(true)
    setError('')
    try {
      const res = await listIncidents()
      setIncidents(res.data)
    } catch (err) {
      setError(getApiError(err).message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchIncidents()
  }, [])

  const handleCreate = async (data: import('./types').CreateIncidentRequest) => {
    setSubmitting(true)
    setSubmitError('')
    try {
      await createIncident(data)
      setShowModal(false)
      fetchIncidents()
    } catch (err) {
      setSubmitError(getApiError(err).message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <section className="page-stack">
      <div className="flex justify-between items-center">
        <div>
          <span className="eyebrow">Administración</span>
          <h1>Cuaderno de Incidencias</h1>
        </div>
        <button className="button primary" onClick={() => setShowModal(true)}>Registrar Incidencia</button>
      </div>

      {showModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            <h3>Nueva Incidencia</h3>
            <IncidentForm onSubmit={handleCreate} isSubmitting={submitting} />
            {submitError && <p className="form-error">{submitError}</p>}
            <button className="button outline mt-4" onClick={() => setShowModal(false)}>Cancelar</button>
          </div>
        </div>
      )}

      {loading ? (
        <OperationalState state="loading" title="Cargando incidencias" message="Consultando el cuaderno de incidencias." />
      ) : error ? (
        <OperationalState state="error" title="No se pudieron cargar las incidencias" message={error} />
      ) : incidents.length === 0 ? (
        <OperationalState state="empty" title="Sin incidencias" message="No se encontraron incidencias registradas." />
      ) : (
        <DataTable rows={incidents} isLoading={false} error={null} columns={[
          { label: 'Fecha', render: (inc) => new Date(inc.occurred_at).toLocaleDateString() },
          { label: 'Alumno', render: (inc) => `Ref. ${inc.student_id}` },
          { label: 'Tipo', render: (inc) => inc.incident_type },
          { label: 'Severidad', render: (inc) => inc.severity },
          { label: 'Estado', render: (inc) => inc.status },
          { label: 'Acciones', render: (inc) => <Link to={`/admin/incidencias/${inc.id}`} className="button button-secondary">Ver detalle</Link> },
        ]} />
      )}
    </section>
  )
}
