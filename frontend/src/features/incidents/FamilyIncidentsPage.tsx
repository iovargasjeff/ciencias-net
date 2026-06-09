import { useEffect, useState } from 'react'
import { listIncidents } from './api'
import type { Incident } from './types'

export function FamilyIncidentsPage() {
  const [incidents, setIncidents] = useState<Incident[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
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
    fetchIncidents()
  }, [])

  return (
    <section className="page-stack">
      <div>
        <span className="eyebrow">Portal Familiar</span>
        <h1>Historial de Incidencias</h1>
        <p>A continuación se muestran los reportes de comportamiento y asistencia registrados.</p>
      </div>

      {loading ? (
        <p>Cargando registros...</p>
      ) : incidents.length === 0 ? (
        <div className="card text-center py-8">
          <h3>No hay incidencias</h3>
          <p>El estudiante no registra incidencias ni reportes.</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {incidents.map(inc => (
            <div key={inc.id} className="card">
              <div className="flex justify-between">
                <h4>{inc.incident_type}</h4>
                <span className="badge">{inc.status}</span>
              </div>
              <p className="text-sm mt-2">{inc.description}</p>
              <div className="mt-4 text-xs color-dim">
                Ocurrió el: {new Date(inc.occurred_at).toLocaleDateString()}
              </div>
            </div>
          ))}
        </div>
      )}
    </section>
  )
}
