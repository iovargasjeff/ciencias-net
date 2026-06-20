import { useEffect, useState } from 'react'
import { DataTable } from '@/components/shared/DataTable'
import { OperationalState } from '@/components/shared/OperationalState'
import { getApiError } from '@/lib/api/client'
import { createPsychologyCare, listPsychologyCare } from './api'
import { PsychologyForm } from './components/PsychologyForm'
import type { CreatePsychologyCareRequest, PsychologyCare } from './types'

export function PsychologyAdminPage() {
  const [items, setItems] = useState<PsychologyCare[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState('')

  const fetchItems = async () => {
    try {
      setLoading(true)
      setError('')
      const res = await listPsychologyCare()
      setItems(res.data)
    } catch (err) {
      setError(getApiError(err).message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void fetchItems()
  }, [])

  const handleSubmit = async (data: CreatePsychologyCareRequest) => {
    try {
      setSubmitting(true)
      setSubmitError('')
      await createPsychologyCare(data)
      setShowForm(false)
      await fetchItems()
    } catch (err) {
      setSubmitError(getApiError(err).message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <section className="page-stack">
      <header>
        <p className="eyebrow">Psicología</p>
        <h1>Bandeja Privada de Psicología</h1>
        <p>Registro confidencial de atenciones a estudiantes con acceso restringido al equipo autorizado.</p>
      </header>

      <div className="panel">
        <div className="panel-header">
          <div>
            <h2>Atenciones registradas</h2>
            <p>La informacion sensible se muestra solo a perfiles con permiso.</p>
          </div>
          <button className="button button-primary" type="button" onClick={() => setShowForm(true)}>
            Registrar Atención
          </button>
        </div>

        {loading ? (
          <OperationalState state="loading" title="Cargando informacion segura" message="Consultando registros privados de psicologia." />
        ) : error ? (
          <OperationalState state="error" title="No se pudieron cargar los registros" message={error} />
        ) : items.length === 0 ? (
          <OperationalState state="empty" title="Sin registros" message="No hay atenciones psicologicas registradas." />
        ) : (
          <DataTable rows={items} isLoading={false} error={null} columns={[
            { label: 'Fecha', render: (item) => new Date(item.occurred_at).toLocaleString() },
            { label: 'Estudiante', render: (item) => `Ref. ${item.student_id}` },
            { label: 'Resumen', render: (item) => item.summary },
            { label: 'Incidencia base', render: (item) => item.incident_id || '-' },
          ]} />
        )}
      </div>

      {showForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3>Nueva Atención Psicológica</h3>
              <button className="button-icon" onClick={() => setShowForm(false)} aria-label="Cerrar">x</button>
            </div>
            <div className="modal-body">
              <PsychologyForm onSubmit={handleSubmit} isSubmitting={submitting} />
              {submitError && <p className="form-error">{submitError}</p>}
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
