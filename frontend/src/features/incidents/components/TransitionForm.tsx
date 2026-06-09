import React, { useState } from 'react'
import type { TransitionIncidentRequest } from '../types'

export function TransitionForm({ onSubmit, isSubmitting }: { onSubmit: (data: TransitionIncidentRequest) => void, isSubmitting: boolean }) {
  const [formData, setFormData] = useState<TransitionIncidentRequest>({
    target_status: 'in_progress',
    reason: ''
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit(formData)
  }

  return (
    <form className="form-layout" onSubmit={handleSubmit}>
      <h4>Actualizar Estado</h4>
      <div className="form-group">
        <label>Nuevo Estado</label>
        <select value={formData.target_status} onChange={(e) => setFormData({ ...formData, target_status: e.target.value as TransitionIncidentRequest['target_status'] })}>
          <option value="in_progress">En proceso</option>
          <option value="referred_toe">Derivar a TOE</option>
          <option value="referred_psychology">Derivar a Psicología</option>
          <option value="parent_notified">Notificar a Padres</option>
          <option value="resolved">Resuelto</option>
          <option value="closed">Cerrado</option>
        </select>
      </div>
      <div className="form-group">
        <label>Razón / Nota de transición</label>
        <textarea required rows={2} value={formData.reason} onChange={(e) => setFormData({ ...formData, reason: e.target.value })} />
      </div>
      <div className="form-actions">
        <button type="submit" disabled={isSubmitting} className="button primary">
          {isSubmitting ? 'Actualizando...' : 'Actualizar'}
        </button>
      </div>
    </form>
  )
}
