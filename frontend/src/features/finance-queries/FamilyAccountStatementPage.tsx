import { useState } from 'react'
import { useAccountStatements } from './hooks'
import { Receipt, Info, WarningCircle, CheckCircle, Money, CalendarBlank, User } from '@phosphor-icons/react'

export function FamilyAccountStatementPage() {
  const { data: statements, isLoading, isError } = useAccountStatements()
  const [activeTab, setActiveTab] = useState<number>(0)

  if (isLoading) {
    return (
      <div className="p-6 space-y-4 animate-pulse">
        <div className="h-8 w-64 bg-slate-200 rounded"></div>
        <div className="flex gap-4 border-b pb-2">
          <div className="h-10 w-32 bg-slate-200 rounded"></div>
          <div className="h-10 w-32 bg-slate-200 rounded"></div>
        </div>
        <div className="h-40 bg-slate-100 rounded"></div>
        <div className="h-64 bg-slate-100 rounded"></div>
      </div>
    )
  }

  if (isError || !statements) {
    return (
      <div className="p-6">
        <div className="bg-red-50 text-red-600 p-4 rounded-lg flex items-center gap-3">
          <WarningCircle size={24} />
          <p>Error al cargar el estado de cuenta. Por favor, intente más tarde.</p>
        </div>
      </div>
    )
  }

  if (statements.length === 0) {
    return (
      <div className="p-6">
        <div className="text-center py-12 bg-slate-50 rounded-lg border border-slate-200">
          <Info size={48} className="mx-auto text-slate-400 mb-4" />
          <h2 className="text-lg font-medium text-slate-700">Sin registros</h2>
          <p className="text-slate-500">No hay información financiera disponible en este momento.</p>
        </div>
      </div>
    )
  }

  const activeStatement = statements[activeTab]

  return (
    <div className="max-w-5xl mx-auto p-4 md:p-6 space-y-6">
      <div className="flex items-center gap-3">
        <Receipt size={32} className="text-blue-600" />
        <h1 className="text-2xl font-bold text-slate-800">Estado de Cuenta Familiar</h1>
      </div>

      {/* Tabs Selector Familiar */}
      <div className="border-b border-slate-200">
        <nav className="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
          {statements.map((stmt, index) => (
            <button
              key={stmt.studentId}
              onClick={() => setActiveTab(index)}
              className={`
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors
                ${activeTab === index
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                }
              `}
            >
              <User size={18} />
              {stmt.studentName}
            </button>
          ))}
        </nav>
      </div>

      {/* Contenido del Hijo Seleccionado */}
      <div className="space-y-6 animation-fade-in">
        {/* Info y Descuentos */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 className="text-sm font-medium text-slate-500 mb-1">Total Adeudado</h3>
            <p className="text-3xl font-bold text-slate-800 flex items-center gap-2">
              <Money size={28} className="text-slate-400" />
              S/ {activeStatement.totalDue.toFixed(2)}
            </p>
          </div>

          {activeStatement.earlyPaymentDiscount && activeStatement.earlyPaymentDiscount.eligible && (
            <div className="bg-green-50 p-5 rounded-xl border border-green-200 shadow-sm flex items-start gap-4">
              <CheckCircle size={28} className="text-green-500 mt-1" />
              <div>
                <h3 className="text-green-800 font-semibold">¡Aviso de Pronto Pago!</h3>
                <p className="text-green-700 text-sm mt-1">
                  Tienes un descuento disponible de S/ {activeStatement.earlyPaymentDiscount.amount.toFixed(2)} si pagas antes del {new Date(activeStatement.earlyPaymentDiscount.deadline).toLocaleDateString()}.
                </p>
              </div>
            </div>
          )}
        </div>

        {/* Lista de Recibos */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 className="font-semibold text-slate-800">Detalle de Recibos</h3>
          </div>
          <div className="divide-y divide-slate-100">
            {activeStatement.receipts.map((receipt) => (
              <div key={receipt.id} className="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:bg-slate-50 transition-colors">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider
                      ${receipt.status === 'paid' ? 'bg-green-100 text-green-700' : 
                        receipt.status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}`}>
                      {receipt.status === 'paid' ? 'Pagado' : receipt.status === 'overdue' ? 'Vencido' : 'Pendiente'}
                    </span>
                    <span className="text-slate-400 text-sm font-mono">{receipt.id}</span>
                  </div>
                  <h4 className="text-slate-800 font-medium">{receipt.description}</h4>
                  <div className="text-slate-500 text-sm flex items-center gap-2 mt-1">
                    <CalendarBlank size={16} />
                    Vence: {new Date(receipt.dueDate).toLocaleDateString()}
                    {receipt.paymentDate && (
                      <span className="text-green-600 ml-2">
                        • Pagado el {new Date(receipt.paymentDate).toLocaleDateString()}
                      </span>
                    )}
                  </div>
                </div>
                <div className="text-right w-full sm:w-auto flex flex-row sm:flex-col justify-between sm:justify-end items-center sm:items-end">
                  <span className="text-lg font-bold text-slate-800">S/ {receipt.amount.toFixed(2)}</span>
                  {receipt.status !== 'paid' && (
                    <button className="text-sm text-blue-600 hover:text-blue-800 font-medium mt-1">
                      Pagar ahora
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
