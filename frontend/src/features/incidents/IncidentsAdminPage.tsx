import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listIncidents, createIncident } from './api'
import type { Incident } from './types'
import { IncidentForm } from './components/IncidentForm'

export function IncidentsAdminPage() {
  const [incidents, setIncidents] = useState<Incident[]>([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const fetchIncidents = async () => {
    setLoading(true)
    try {
      const res = await listIncidents()
      setIncidents(res.data)
    } catch (err) {
      console.error(err)
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
    try {
      await createIncident(data)
      setShowModal(false)
      fetchIncidents()
    } catch (err) {
      console.error(err)
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
            <button className="button outline mt-4" onClick={() => setShowModal(false)}>Cancelar</button>
          </div>
        </div>
      )}

      {loading ? (
        <p>Cargando incidencias...</p>
      ) : incidents.length === 0 ? (
        <p>No se encontraron incidencias registradas.</p>
      ) : (
        <div className="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Alumno ID</th>
                <th>Tipo</th>
                <th>Severidad</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {incidents.map((inc) => (
                <tr key={inc.id}>
                  <td>{new Date(inc.occurred_at).toLocaleDateString()}</td>
                  <td>{inc.student_id}</td>
                  <td>{inc.incident_type}</td>
                  <td>{inc.severity}</td>
                  <td>{inc.status}</td>
                  <td><Link to={`/admin/incidencias/${inc.id}`} className="button small">Ver Detalle</Link></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  )
}
