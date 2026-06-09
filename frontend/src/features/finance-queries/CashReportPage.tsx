import { useCashReport } from './hooks'
import { Money, Bank, CreditCard, WarningCircle } from '@phosphor-icons/react'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts'
import { OperationalState } from '../../components/shared/OperationalState'

const COLORS = ['#3b82f6', '#10b981', '#f59e0b']

const METHOD_LABELS = {
  cash: 'Efectivo',
  transfer: 'Transferencia',
  card: 'Tarjeta'
}

const METHOD_ICONS = {
  cash: <Money size={24} />,
  transfer: <Bank size={24} />,
  card: <CreditCard size={24} />
}

export function CashReportPage() {
  const { data: report, isLoading, error } = useCashReport()

  if (isLoading) {
    return <OperationalState state="loading" title="Cargando Reporte" message="Calculando ingresos y métodos de pago..." />
  }

  if (error) {
    return (
      <div className="p-6 max-w-7xl mx-auto">
        <div className="bg-red-50 border border-red-200 p-6 rounded-xl text-center space-y-4">
          <WarningCircle size={48} className="text-red-500 mx-auto" />
          <div>
            <h2 className="text-xl font-bold text-red-700">Error al Cargar Reporte</h2>
            <p className="text-red-600 mt-1">{error.message}</p>
          </div>
          <button 
            onClick={() => window.location.reload()}
            className="px-4 py-2 bg-white text-red-600 font-medium rounded border border-red-200 hover:bg-red-50"
          >
            Reintentar
          </button>
        </div>
      </div>
    )
  }

  if (!report) return null

  const pieData = report.incomeByMethod.map(item => ({
    name: METHOD_LABELS[item.method],
    value: item.amount
  }))

  const barData = report.dailyData.map(item => ({
    date: new Date(item.date).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }),
    amount: item.amount
  }))

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">Cierre de Caja</h1>
          <p className="text-slate-500">Reporte del {new Date(report.date).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        <button className="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
          Exportar PDF
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm col-span-1 lg:col-span-1">
          <h3 className="text-sm font-medium text-slate-500 mb-1">Ingreso Total</h3>
          <p className="text-3xl font-bold text-slate-800">S/ {report.totalIncome.toFixed(2)}</p>
        </div>

        {report.incomeByMethod.map((method) => (
          <div key={method.method} className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
              <h3 className="text-sm font-medium text-slate-500 mb-1">{METHOD_LABELS[method.method]}</h3>
              <p className="text-2xl font-bold text-slate-700">S/ {method.amount.toFixed(2)}</p>
            </div>
            <div className={`p-3 rounded-full ${
              method.method === 'cash' ? 'bg-green-100 text-green-600' :
              method.method === 'transfer' ? 'bg-blue-100 text-blue-600' :
              'bg-amber-100 text-amber-600'
            }`}>
              {METHOD_ICONS[method.method]}
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm col-span-1 lg:col-span-2">
          <h3 className="font-bold text-slate-800 mb-6">Ingresos de los últimos 7 días</h3>
          <div className="h-80 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={barData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fill: '#64748b' }} dy={10} />
                <YAxis axisLine={false} tickLine={false} tick={{ fill: '#64748b' }} tickFormatter={(val) => `S/ ${val}`} />
                <Tooltip 
                  cursor={{ fill: '#f1f5f9' }}
                  contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                  formatter={(value: unknown) => [`S/ ${Number(value).toFixed(2)}`, 'Ingresos']}
                />
                <Bar dataKey="amount" fill="#3b82f6" radius={[4, 4, 0, 0]} barSize={40} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 className="font-bold text-slate-800 mb-6">Distribución por Método</h3>
          <div className="h-80 w-full flex flex-col items-center justify-center">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={pieData}
                  cx="50%"
                  cy="45%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {pieData.map((_entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip 
                  formatter={(value: unknown) => `S/ ${Number(value).toFixed(2)}`}
                  contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0' }}
                />
                <Legend verticalAlign="bottom" height={36} iconType="circle" />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  )
}
