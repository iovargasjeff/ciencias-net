import { useState } from 'react'
import type { CreateIncidentFollowUpRequest } from '../types'

export function FollowUpForm({ onSubmit, isSubmitting }: { onSubmit: (data: CreateIncidentFollowUpRequest) => void, isSubmitting: boolean }) {
  const [formData, setFormData] = useState<CreateIncidentFollowUpRequest>({ note: '' })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit(formData)
  }

  return (
    <form className="form-layout" onSubmit={handleSubmit}>
      <h4>Agregar Seguimiento</h4>
      <div className="form-group">
        <label>Nota de seguimiento</label>
        <textarea required rows={3} value={formData.note} onChange={(e) => setFormData({ ...formData, note: e.target.value })} placeholder="Escribe los detalles del seguimiento..." />
      </div>
      <div className="form-group">
        <label>Evidencia (opcional)</label>
        <input type="file" onChange={(e) => setFormData({ ...formData, file: e.target.files?.[0] || null })} />
      </div>
      <div className="form-actions">
        <button type="submit" disabled={isSubmitting} className="button primary">
          {isSubmitting ? 'Guardando...' : 'Agregar Nota'}
        </button>
      </div>
    </form>
  )
}
