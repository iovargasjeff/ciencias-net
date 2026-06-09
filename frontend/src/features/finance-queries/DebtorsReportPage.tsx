import { useState } from 'react'
import { useDebtors, useSendPaymentReminders } from './hooks'
import { DataTable } from '../../components/shared/DataTable'
import { Users, EnvelopeSimple } from '@phosphor-icons/react'
import type { Debtor } from './types'

export function DebtorsReportPage() {
  const { data: debtors, isLoading, error } = useDebtors()
  const { mutate: sendReminders, isPending } = useSendPaymentReminders()
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set())
  const [successMessage, setSuccessMessage] = useState('')

  // Usamos el id de la interfaz Debtor o studentId como clave única
  const debtorsWithId = debtors?.map(d => ({ ...d, id: d.studentId }))

  const handleSelectAll = () => {
    if (debtorsWithId && selectedIds.size === debtorsWithId.length) {
      setSelectedIds(new Set())
    } else if (debtorsWithId) {
      setSelectedIds(new Set(debtorsWithId.map(d => d.id)))
    }
  }

  const handleSelect = (id: string) => {
    const newSelected = new Set(selectedIds)
    if (newSelected.has(id)) {
      newSelected.delete(id)
    } else {
      newSelected.add(id)
    }
    setSelectedIds(newSelected)
  }

  const handleSendReminders = () => {
    if (selectedIds.size === 0) return
    
    sendReminders({ debtorIds: Array.from(selectedIds) }, {
      onSuccess: () => {
        setSuccessMessage(`Recordatorios enviados correctamente a ${selectedIds.size} apoderados.`)
        setSelectedIds(new Set())
        setTimeout(() => setSuccessMessage(''), 5000)
      }
    })
  }

  const columns = [
    {
      label: 'Seleccionar',
      render: (row: Debtor & { id: string }) => (
        <input 
          type="checkbox" 
          checked={selectedIds.has(row.id)}
          onChange={() => handleSelect(row.id)}
          className="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
        />
      )
    },
    {
      label: 'Alumno',
      render: (row: Debtor) => row.studentName
    },
    {
      label: 'Apoderado / Email',
      render: (row: Debtor) => (
        <div>
          <div className="font-medium">{row.guardianName}</div>
          <div className="text-sm text-slate-500">{row.guardianEmail}</div>
        </div>
      )
    },
    {
      label: 'Deuda Vencida',
      render: (row: Debtor) => <span className="font-bold text-red-600">S/ {row.overdueAmount.toFixed(2)}</span>
    },
    {
      label: 'Recibos',
      render: (row: Debtor) => row.overdueReceiptsCount
    },
    {
      label: 'Días Mora',
      render: (row: Debtor) => (
        <span className={`px-2 py-1 rounded-full text-xs font-medium ${row.daysOverdue > 30 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'}`}>
          {row.daysOverdue} días
        </span>
      )
    }
  ]

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div className="flex items-center gap-3">
          <div className="bg-red-100 p-2 rounded-lg text-red-600">
            <Users size={28} />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-slate-800">Reporte de Morosidad</h1>
            <p className="text-slate-500">Gestión de cobranzas y deudas vencidas</p>
          </div>
        </div>
        
        <div className="flex gap-3">
          <button
            onClick={handleSelectAll}
            className="px-4 py-2 text-sm font-medium border border-slate-300 rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition-colors"
          >
            {debtorsWithId && selectedIds.size === debtorsWithId.length ? 'Deseleccionar Todos' : 'Seleccionar Todos'}
          </button>
          
          <button
            onClick={handleSendReminders}
            disabled={selectedIds.size === 0 || isPending}
            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors
              ${selectedIds.size === 0 || isPending 
                ? 'bg-slate-100 text-slate-400 cursor-not-allowed' 
                : 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm'}`}
          >
            <EnvelopeSimple size={18} />
            {isPending ? 'Enviando...' : `Enviar Recordatorios (${selectedIds.size})`}
          </button>
        </div>
      </div>

      {successMessage && (
        <div className="bg-green-50 text-green-700 p-4 rounded-lg flex items-center gap-2 border border-green-200 animation-fade-in">
          <EnvelopeSimple size={20} />
          {successMessage}
        </div>
      )}

      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <DataTable
          columns={columns}
          rows={debtorsWithId}
          isLoading={isLoading}
          error={error}
        />
      </div>
    </div>
  )
}
