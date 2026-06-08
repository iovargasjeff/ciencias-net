import { useState } from 'react'
import { WarningCircle, DownloadSimple } from '@phosphor-icons/react'
import { useQueryClient, useMutation } from '@tanstack/react-query'
import { usePaymentMovements } from './hooks'
import { annulPaymentMovement } from './api'
import type { PaymentMovement } from './types'

export function PaymentsPage() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState<string>('')
  const client = useQueryClient()

  const { data, isLoading, isError, error, refetch } = usePaymentMovements(search, status)

  const annulMutation = useMutation({
    mutationFn: (id: string) => annulPaymentMovement(id, 'Error en el pago registrado'),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: ['payment-movements'] })
      client.invalidateQueries({ queryKey: ['payment-obligations'] })
    }
  })

  return (
    <div className="p-8 max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Histórico de Pagos</h1>
          <p className="text-muted-foreground mt-2">Consulta los movimientos y descarga recibos inmutables.</p>
        </div>
      </div>

      <div className="flex gap-4 mb-6">
        <input 
          type="text" 
          placeholder="Buscar por alumno o recibo..." 
          className="border rounded-md px-4 py-2 w-full max-w-sm"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select 
          className="border rounded-md px-4 py-2" 
          value={status} 
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">Todos los estados</option>
          <option value="completed">Completado</option>
          <option value="annulled">Anulado</option>
          <option value="refunded">Devuelto</option>
        </select>
      </div>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 bg-gray-100 animate-pulse rounded-md"></div>
          ))}
        </div>
      ) : isError ? (
        <div className="bg-red-50 text-red-800 p-4 rounded-md flex items-start gap-3">
          <WarningCircle size={24} className="mt-0.5" />
          <div>
            <h3 className="font-semibold">Error al cargar movimientos</h3>
            <p className="text-sm mt-1">{error instanceof Error ? error.message : 'Error desconocido'}</p>
            <button onClick={() => refetch()} className="mt-3 text-sm underline font-medium">Reintentar</button>
          </div>
        </div>
      ) : data?.data?.length === 0 ? (
        <div className="text-center py-12 border-2 border-dashed rounded-lg text-gray-500">
          <p className="text-lg">No hay movimientos de pago registrados.</p>
        </div>
      ) : (
        <div className="border rounded-md overflow-hidden bg-white shadow-sm">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="px-6 py-3 font-semibold text-gray-700">Recibo</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Fecha</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Alumno / Deuda</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Monto</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Medio / Ref</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Estado</th>
                <th className="px-6 py-3 font-semibold text-gray-700 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {data?.data?.map((m: PaymentMovement) => (
                <tr key={m.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 font-mono font-medium">{m.receipt_number || '-'}</td>
                  <td className="px-6 py-4 text-xs">{new Date(m.created_at).toLocaleDateString()}</td>
                  <td className="px-6 py-4">
                    <div className="font-medium">{m.student_name}</div>
                    <div className="text-xs text-gray-500">{m.obligation_concept_name}</div>
                  </td>
                  <td className="px-6 py-4 font-semibold text-green-700">S/ {m.amount_paid}</td>
                  <td className="px-6 py-4">
                    <div className="capitalize">{m.payment_method}</div>
                    <div className="text-xs text-gray-500">{m.reference || 'Sin ref.'}</div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                      m.status === 'completed' ? 'bg-green-100 text-green-800' :
                      'bg-red-100 text-red-800'
                    }`}>
                      {m.status === 'completed' ? 'Completado' : m.status === 'annulled' ? 'Anulado' : 'Devuelto'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                    <button 
                      className="text-gray-600 hover:text-blue-600 text-sm flex items-center gap-1 ml-auto justify-end mb-2" 
                    >
                      <DownloadSimple /> Recibo
                    </button>
                    <button 
                      className="text-red-600 hover:text-red-800 text-xs disabled:opacity-30"
                      disabled={m.status !== 'completed' || annulMutation.isPending}
                      onClick={() => {
                        if(confirm('¿Seguro que deseas ANULAR este pago? Esto reabrirá la deuda.')) {
                          annulMutation.mutate(m.id)
                        }
                      }}
                    >
                      {annulMutation.isPending && annulMutation.variables === m.id ? 'Anulando...' : 'Anular'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
