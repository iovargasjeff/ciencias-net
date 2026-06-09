import { useEffect, useState } from 'react'
import { listPsychologyCare, createPsychologyCare } from './api'
import type { PsychologyCare, CreatePsychologyCareRequest } from './types'
import { PsychologyForm } from './components/PsychologyForm'

export function PsychologyAdminPage() {
  const [items, setItems] = useState<PsychologyCare[]>([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const fetchItems = async () => {
    try {
      setLoading(true)
      const res = await listPsychologyCare()
      setItems(res.data)
    } catch (error) {
      console.error(error)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchItems()
  }, [])

  const handleSubmit = async (data: CreatePsychologyCareRequest) => {
    try {
      setSubmitting(true)
      await createPsychologyCare(data)
      setShowForm(false)
      fetchItems()
    } catch (error) {
      console.error(error)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <section className="page-stack">
      <header className="page-header">
        <h1>Bandeja Privada de Psicología</h1>
        <p className="page-description">
          Registro clínico y atención confidencial a estudiantes. El acceso a esta bandeja está restringido a profesionales de Psicología.
        </p>
      </header>

      <div className="table-controls">
        <button className="button button-primary" onClick={() => setShowForm(true)}>
          Registrar Atención
        </button>
      </div>

      {loading ? (
        <p>Cargando información segura...</p>
      ) : (
        <div className="table-container">
          <table className="data-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Estudiante</th>
                <th>Resumen</th>
                <th>Incidencia Base</th>
              </tr>
            </thead>
            <tbody>
              {items.map(item => (
                <tr key={item.id}>
                  <td>{new Date(item.occurred_at).toLocaleString()}</td>
                  <td>{item.student_id}</td>
                  <td>{item.summary}</td>
                  <td>{item.incident_id || '-'}</td>
                </tr>
              ))}
              {items.length === 0 && (
                <tr>
                  <td colSpan={4} className="text-center">No hay registros</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {showForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3>Nueva Atención Psicológica</h3>
              <button className="button-icon" onClick={() => setShowForm(false)} aria-label="Cerrar">✕</button>
            </div>
            <div className="modal-body">
              <PsychologyForm onSubmit={handleSubmit} isSubmitting={submitting} />
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
