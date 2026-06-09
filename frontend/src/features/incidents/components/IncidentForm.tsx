import { useState } from 'react'
import type { CreateIncidentRequest } from '../types'

export function IncidentForm({ onSubmit, isSubmitting, studentId }: { onSubmit: (data: CreateIncidentRequest) => void, isSubmitting: boolean, studentId?: string }) {
  const [formData, setFormData] = useState<CreateIncidentRequest>({
    student_id: studentId || '',
    incident_type: '',
    severity: 'low',
    description: '',
    occurred_at: new Date().toISOString().slice(0, 16)
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit(formData)
  }

  return (
    <form className="form-layout" onSubmit={handleSubmit}>
      <div className="form-group">
        <label>ID del Alumno</label>
        <input required type="text" value={formData.student_id} onChange={(e) => setFormData({ ...formData, student_id: e.target.value })} disabled={!!studentId} />
      </div>
      <div className="form-group">
        <label>Tipo de Incidencia</label>
        <input required type="text" value={formData.incident_type} onChange={(e) => setFormData({ ...formData, incident_type: e.target.value })} placeholder="Ej. Tardanza, Faltamiento..." />
      </div>
      <div className="form-group">
        <label>Severidad</label>
        <select value={formData.severity} onChange={(e) => setFormData({ ...formData, severity: e.target.value as import('../types').CreateIncidentRequest['severity'] })}>
          <option value="low">Leve</option>
          <option value="medium">Moderado</option>
          <option value="high">Grave</option>
          <option value="critical">Crítico</option>
        </select>
      </div>
      <div className="form-group">
        <label>Descripción detallada</label>
        <textarea required rows={4} value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} />
      </div>
      <div className="form-group">
        <label>Fecha y hora de ocurrencia</label>
        <input required type="datetime-local" value={formData.occurred_at} onChange={(e) => setFormData({ ...formData, occurred_at: e.target.value })} />
      </div>
      <div className="form-actions">
        <button type="submit" disabled={isSubmitting} className="button primary">
          {isSubmitting ? 'Registrando...' : 'Registrar Incidencia'}
        </button>
      </div>
    </form>
  )
}
