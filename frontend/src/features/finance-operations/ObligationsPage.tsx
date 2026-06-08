import { useState } from 'react'
import { Plus, WarningCircle } from '@phosphor-icons/react'
import { usePaymentObligations } from './hooks'
import { GenerateObligationForm, AdjustObligationForm } from './ObligationForms'
import { RegisterPaymentForm } from './RegisterPaymentForm'
import type { PaymentObligation } from './types'

export function ObligationsPage() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState<string>('')
  
  const [isGenerateOpen, setIsGenerateOpen] = useState(false)
  const [adjustingObligation, setAdjustingObligation] = useState<PaymentObligation | null>(null)
  const [payingObligation, setPayingObligation] = useState<PaymentObligation | null>(null)

  const { data, isLoading, isError, error, refetch } = usePaymentObligations(search, status)

  return (
    <div className="p-8 max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Obligaciones (Deudas)</h1>
          <p className="text-muted-foreground mt-2">Consulta y gestiona las deudas pendientes de los alumnos.</p>
        </div>
        <button 
          onClick={() => setIsGenerateOpen(true)}
          className="btn btn-primary flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          <Plus weight="bold" /> Generar Deudas
        </button>
      </div>

      <div className="flex gap-4 mb-6">
        <input 
          type="text" 
          placeholder="Buscar por alumno o concepto..." 
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
          <option value="pending">Pendiente</option>
          <option value="paid">Pagado</option>
          <option value="annulled">Anulado</option>
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
            <h3 className="font-semibold">Error al cargar obligaciones</h3>
            <p className="text-sm mt-1">{error instanceof Error ? error.message : 'Error desconocido'}</p>
            <button onClick={() => refetch()} className="mt-3 text-sm underline font-medium">Reintentar</button>
          </div>
        </div>
      ) : data?.data?.length === 0 ? (
        <div className="text-center py-12 border-2 border-dashed rounded-lg text-gray-500">
          <p className="text-lg">No se encontraron obligaciones con los filtros actuales.</p>
        </div>
      ) : (
        <div className="border rounded-md overflow-hidden bg-white shadow-sm">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="px-6 py-3 font-semibold text-gray-700">Alumno</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Concepto</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Monto Base</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Vencimiento</th>
                <th className="px-6 py-3 font-semibold text-gray-700">Estado</th>
                <th className="px-6 py-3 font-semibold text-gray-700 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {data?.data?.map((ob: PaymentObligation) => (
                <tr key={ob.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 font-medium">{ob.student_name}</td>
                  <td className="px-6 py-4">
                    {ob.concept_name}
                    {ob.early_payment_amount && ob.status === 'pending' && (
                      <div className="text-xs text-green-600 mt-1">
                        Pronto Pago: S/ {ob.early_payment_amount} hasta {ob.early_payment_deadline}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4">S/ {ob.base_amount}</td>
                  <td className="px-6 py-4">{ob.due_date}</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                      ob.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                      ob.status === 'paid' ? 'bg-green-100 text-green-800' :
                      'bg-gray-100 text-gray-800'
                    }`}>
                      {ob.status === 'pending' ? 'Pendiente' : ob.status === 'paid' ? 'Pagado' : 'Anulado'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right space-x-3">
                    <button 
                      className="text-gray-600 hover:text-blue-600 text-sm disabled:opacity-30 disabled:hover:text-gray-600" 
                      disabled={ob.status !== 'pending'}
                      onClick={() => setAdjustingObligation(ob)}
                    >
                      Ajustar
                    </button>
                    <button 
                      className="text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-md text-sm font-medium disabled:opacity-50"
                      disabled={ob.status !== 'pending'}
                      onClick={() => setPayingObligation(ob)}
                    >
                      Pagar
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {isGenerateOpen && <GenerateObligationForm onSuccess={() => setIsGenerateOpen(false)} onCancel={() => setIsGenerateOpen(false)} />}
      {adjustingObligation && <AdjustObligationForm obligation={adjustingObligation} onSuccess={() => setAdjustingObligation(null)} onCancel={() => setAdjustingObligation(null)} />}
      {payingObligation && <RegisterPaymentForm obligation={payingObligation} onSuccess={() => setPayingObligation(null)} onCancel={() => setPayingObligation(null)} />}
    </div>
  )
}
