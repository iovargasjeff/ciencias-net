import { useState } from 'react'
import type { CreatePsychologyCareRequest } from '../types'

export function PsychologyForm({ onSubmit, isSubmitting }: { onSubmit: (data: CreatePsychologyCareRequest) => void, isSubmitting: boolean }) {
  const [formData, setFormData] = useState<CreatePsychologyCareRequest>({
    student_id: '',
    occurred_at: new Date().toISOString().slice(0, 16),
    summary: '',
    confidential_notes: '',
    incident_id: ''
  })

  return (
    <form className="form-stack" onSubmit={(e) => {
      e.preventDefault()
      onSubmit(formData)
    }}>
      <div className="form-group">
        <label>ID del Alumno</label>
        <input required type="text" value={formData.student_id} onChange={(e) => setFormData({ ...formData, student_id: e.target.value })} />
      </div>

      <div className="form-group">
        <label>Fecha y Hora</label>
        <input required type="datetime-local" value={formData.occurred_at} onChange={(e) => setFormData({ ...formData, occurred_at: e.target.value })} />
      </div>

      <div className="form-group">
        <label>Resumen de Atención</label>
        <input required type="text" placeholder="Ej. Entrevista preliminar..." value={formData.summary} onChange={(e) => setFormData({ ...formData, summary: e.target.value })} />
      </div>

      <div className="form-group">
        <label>Notas Confidenciales</label>
        <textarea rows={5} placeholder="Registro clínico o información sensible..." value={formData.confidential_notes} onChange={(e) => setFormData({ ...formData, confidential_notes: e.target.value })} />
      </div>

      <div className="form-group">
        <label>ID de Incidencia (Opcional)</label>
        <input type="text" value={formData.incident_id} onChange={(e) => setFormData({ ...formData, incident_id: e.target.value })} />
      </div>

      <div className="form-actions">
        <button type="submit" className="button button-primary" disabled={isSubmitting}>
          {isSubmitting ? 'Guardando...' : 'Registrar Atención'}
        </button>
      </div>
    </form>
  )
}
