import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Coins,
  Clock,
  SpinnerGap,
  Calculator,
  LockKey,
  ShieldCheck,
  FilePdf,
  XCircle,
  UserGear,
  UserPlus
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { DataTable } from '@/components/shared/DataTable'
import { OperationalState } from '@/components/shared/OperationalState'
import { getApiError } from '@/lib/api/client'
import { listAccounts } from '@/features/phase-one/api'
import {
  listTeacherAttendance,
  listTeacherRates,
  createTeacherRate,
  listPayrollLiquidations,
  createPayrollLiquidation,
  closePayrollLiquidation,
  createTeacherAttendanceAdjustment,
  cancelClassSession,
  assignClassSessionSubstitute,
  generateTeacherPayrollReport
} from './api'
import type { TeacherAttendance, PayrollLiquidation } from './types'

export function PayrollAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()

  // 1. Centralized permission check
  const hasAccess = user?.roles.includes('superadmin') || user?.permissions.includes('gestionar_planilla')
  const canCloseLiquidation = user?.roles.includes('superadmin') || user?.permissions.includes('cerrar_liquidacion')
  const canCancelClass = user?.roles.includes('superadmin') || user?.roles.includes('coordinador_academico')

  // Tabs state: 'attendance' | 'rates' | 'liquidations'
  const [activeTab, setActiveTab] = useState<'attendance' | 'rates' | 'liquidations'>('attendance')

  // Cache invalidation helper
  const invalidate = async (key: string) => client.invalidateQueries({ queryKey: [key] })

  // Queries
  const [dateFilter, setDateFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [searchFilter, setSearchFilter] = useState('')

  const attendanceQuery = useQuery({
    queryKey: ['teacher-attendance', dateFilter, statusFilter, searchFilter],
    queryFn: () => listTeacherAttendance({ date: dateFilter || undefined, status: statusFilter || undefined, search: searchFilter || undefined })
  })

  const ratesQuery = useQuery({
    queryKey: ['teacher-rates'],
    queryFn: listTeacherRates
  })

  const liquidationsQuery = useQuery({
    queryKey: ['payroll-liquidations'],
    queryFn: listPayrollLiquidations
  })

  const accountsQuery = useQuery({
    queryKey: ['accounts-teachers'],
    queryFn: () => listAccounts()
  })

  const teachers = accountsQuery.data?.data.filter(a => a.roles.includes('docente') || a.roles.includes('superadmin')) || []

  // --- TAB 1: Attendance State & Mutations ---
  const [selectedLog, setSelectedLog] = useState<TeacherAttendance | null>(null)
  const [logActionType, setLogActionType] = useState<'adjust' | 'substitute' | 'cancel' | null>(null)
  const [adjustmentMinutes, setAdjustmentMinutes] = useState(0)
  const [adjustmentType, setAdjustmentType] = useState<'add' | 'subtract'>('add')
  const [actionReason, setActionReason] = useState('')
  const [actionError, setActionError] = useState('')
  const [substituteTeacherId, setSubstituteTeacherId] = useState('')

  const adjustMutation = useMutation({
    mutationFn: createTeacherAttendanceAdjustment,
    onSuccess: async () => {
      setSelectedLog(null)
      setLogActionType(null)
      setActionReason('')
      setAdjustmentMinutes(0)
      setActionError('')
      await invalidate('teacher-attendance')
      await invalidate('payroll-liquidations')
    },
    onError: (err) => setActionError(getApiError(err).message)
  })

  const substituteMutation = useMutation({
    mutationFn: ({ classSessionId, teacherId }: { classSessionId: string; teacherId: string }) =>
      assignClassSessionSubstitute(classSessionId, { teacher_id: teacherId }),
    onSuccess: async () => {
      setSelectedLog(null)
      setLogActionType(null)
      setActionReason('')
      setSubstituteTeacherId('')
      setActionError('')
      await invalidate('teacher-attendance')
    },
    onError: (err) => setActionError(getApiError(err).message)
  })

  const cancelMutation = useMutation({
    mutationFn: ({ classSessionId, reason }: { classSessionId: string; reason: string }) =>
      cancelClassSession(classSessionId, reason),
    onSuccess: async () => {
      setSelectedLog(null)
      setLogActionType(null)
      setActionReason('')
      setActionError('')
      await invalidate('teacher-attendance')
    },
    onError: (err) => setActionError(getApiError(err).message)
  })

  // --- TAB 2: Rates State & Mutations ---
  const [rateTeacherId, setRateTeacherId] = useState('')
  const [hourlyRate, setHourlyRate] = useState('')
  const [effectiveFrom, setEffectiveFrom] = useState('')
  const [effectiveUntil, setEffectiveUntil] = useState('')
  const [rateSuccess, setRateSuccess] = useState('')
  const [rateError, setRateError] = useState('')

  const rateMutation = useMutation({
    mutationFn: createTeacherRate,
    onSuccess: async () => {
      setRateTeacherId('')
      setHourlyRate('')
      setEffectiveFrom('')
      setEffectiveUntil('')
      setRateSuccess('Tarifa por hora configurada exitosamente.')
      setRateError('')
      await invalidate('teacher-rates')
    },
    onError: (err) => {
      setRateSuccess('')
      setRateError(getApiError(err).message)
    }
  })

  // --- TAB 3: Liquidations State & Mutations ---
  const [liqStart, setLiqStart] = useState('')
  const [liqEnd, setLiqEnd] = useState('')
  const [liqSuccess, setLiqSuccess] = useState('')
  const [liqError, setLiqError] = useState('')
  const [inspectLiquidation, setInspectLiquidation] = useState<PayrollLiquidation | null>(null)
  const [showFormulaFor, setShowFormulaFor] = useState<string | null>(null) // Item ID
  const [confirmClose, setConfirmClose] = useState(false)
  const [reportFormat, setReportFormat] = useState<'pdf' | 'xlsx'>('pdf')
  const [reportMessage, setReportMessage] = useState('')

  const createLiquidationMutation = useMutation({
    mutationFn: createPayrollLiquidation,
    onSuccess: async () => {
      setLiqStart('')
      setLiqEnd('')
      setLiqSuccess('Periodo de liquidación mensual creado con éxito.')
      setLiqError('')
      await invalidate('payroll-liquidations')
    },
    onError: (err) => {
      setLiqSuccess('')
      setLiqError(getApiError(err).message)
    }
  })

  const closeLiquidationMutation = useMutation({
    mutationFn: closePayrollLiquidation,
    onSuccess: async () => {
      setConfirmClose(false)
      setInspectLiquidation((prev) => (prev ? { ...prev, status: 'closed' } : null))
      await invalidate('payroll-liquidations')
    },
    onError: (err) => {
      alert(`Error al cerrar planilla: ${getApiError(err).message}`)
    }
  })

  const generateReportMutation = useMutation({
    mutationFn: generateTeacherPayrollReport,
    onSuccess: () => {
      setReportMessage('Reporte generado correctamente. Iniciando descarga simulada...')
      setTimeout(() => setReportMessage(''), 4000)
    },
    onError: (err) => {
      setReportMessage(`Error al generar reporte: ${getApiError(err).message}`)
    }
  })

  // Permiso general bloqueante
  if (!hasAccess) {
    return (
      <OperationalState
        state="forbidden"
        title="Acceso Denegado"
        message="Su cuenta no tiene los permisos necesarios (gestionar_planilla) para administrar el control de asistencia docente y planillas."
      />
    )
  }

  // Comprobar si existe una liquidación cerrada que abarque la fecha actual o el registro consultado
  const isDateInClosedLiquidation = (dateStr: string) => {
    if (!liquidationsQuery.data?.data) return false
    const date = new Date(dateStr)
    return liquidationsQuery.data.data.some((liq) => {
      if (liq.status !== 'closed') return false
      const start = new Date(liq.period_start)
      const end = new Date(liq.period_end)
      return date >= start && date <= end
    })
  }

  return (
    <section className="page-stack dashboard-light-bg p-6 rounded-3xl border border-slate-100/80 shadow-sm text-slate-800">
      <header className="space-y-1">
        <p className="eyebrow text-blue-600 font-extrabold tracking-wider text-xs flex items-center gap-1.5">
          <Coins size={14} weight="bold" />
          Administración Financiera
        </p>
        <h1 className="text-3xl font-black text-slate-900 tracking-tight">Asistencia Docente y Planilla</h1>
        <p className="text-slate-500 text-sm">Gestiona tarifas por hora de docentes, registra correcciones o sustitutos y realiza el cierre de liquidaciones mensuales.</p>
      </header>

      {/* Tabs navigation */}
      <div className="flex bg-slate-100 p-1.5 rounded-2xl mb-4 max-w-lg gap-1.5 shadow-inner border border-slate-200/50">
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center cursor-pointer ${
            activeTab === 'attendance' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => setActiveTab('attendance')}
        >
          Asistencia Docente
        </button>
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center cursor-pointer ${
            activeTab === 'rates' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => setActiveTab('rates')}
        >
          Tarifas por Hora
        </button>
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center cursor-pointer ${
            activeTab === 'liquidations' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => setActiveTab('liquidations')}
        >
          Liquidación de Planilla
        </button>
      </div>

      {/* TAB 1: Asistencia Docente */}
      {activeTab === 'attendance' && (
        <div className="space-y-4">
          <div className="glass-panel-light p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Clock className="text-blue-600" size={22} weight="bold" />
              Asistencias del Periodo
            </h2>
            <div className="flex flex-wrap items-center gap-2.5">
              <label className="text-xs font-bold text-slate-600 flex flex-col cursor-pointer">
                Fecha
                <input
                  type="date"
                  className="glass-input-light mt-1 p-2 rounded-xl text-xs"
                  value={dateFilter}
                  onChange={(e) => setDateFilter(e.target.value)}
                />
              </label>
              <label className="text-xs font-bold text-slate-600 flex flex-col cursor-pointer">
                Estado
                <select
                  className="glass-input-light mt-1 p-2 rounded-xl text-xs cursor-pointer"
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                >
                  <option value="">Todos</option>
                  <option value="present">Presente</option>
                  <option value="late">Tardanza</option>
                  <option value="absent">Falta</option>
                  <option value="excused">Justificada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </label>
              <label className="text-xs font-bold text-slate-600 flex flex-col cursor-pointer">
                Buscar Docente
                <input
                  type="text"
                  className="glass-input-light mt-1 p-2 rounded-xl text-xs"
                  placeholder="Nombre..."
                  value={searchFilter}
                  onChange={(e) => setSearchFilter(e.target.value)}
                />
              </label>
              <button
                className="button button-secondary text-xs px-3 py-2 mt-4 cursor-pointer font-bold"
                onClick={() => {
                  setDateFilter('')
                  setStatusFilter('')
                  setSearchFilter('')
                }}
              >
                Limpiar Filtros
              </button>
            </div>
          </div>

          <div className="glass-panel-light p-6 rounded-2xl">
            <DataTable
              rows={attendanceQuery.data?.data}
              isLoading={attendanceQuery.isLoading}
              error={attendanceQuery.error as Error}
              columns={[
                {
                  label: 'Docente / Sesión',
                  render: (log) => (
                    <div className="py-1">
                      <strong className="text-slate-900 font-bold text-sm">{log.teacher_name}</strong>
                      <small className="text-slate-500 font-mono text-xs block">Fecha: {log.date}</small>
                    </div>
                  )
                },
                {
                  label: 'Horario / Marcación',
                  render: (log) => (
                    <div className="text-xs text-slate-700 font-medium">
                      <div>Entrada: {log.entry_time ? new Date(log.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '--:--'}</div>
                      <div>Salida: {log.exit_time ? new Date(log.exit_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '--:--'}</div>
                    </div>
                  )
                },
                {
                  label: 'Cálculo Tardanza / Falta',
                  render: (log) => (
                    <div className="text-xs text-slate-600">
                      {log.minutes_late > 0 && <div className="text-amber-700 font-semibold">Tardanza: {log.minutes_late} min</div>}
                      {log.hours_absent > 0 && <div className="text-rose-700 font-semibold">Falta: {log.hours_absent} hrs</div>}
                      {log.minutes_late === 0 && log.hours_absent === 0 && <span className="text-slate-400">Sin descuentos</span>}
                    </div>
                  )
                },
                {
                  label: 'Sustituto / Cancelación',
                  render: (log) => (
                    <div className="text-xs text-slate-600">
                      {log.substitute_teacher_name && (
                        <div className="text-blue-700 font-medium flex items-center gap-1">
                          <UserGear size={14} /> Sust: {log.substitute_teacher_name}
                        </div>
                      )}
                      {log.status === 'cancelled' && (
                        <div className="text-slate-500 italic">Clase Cancelada: {log.reason || 'Sin motivo'}</div>
                      )}
                      {!log.substitute_teacher_name && log.status !== 'cancelled' && <span className="text-slate-400">-</span>}
                    </div>
                  )
                },
                {
                  label: 'Estado',
                  render: (log) => (
                    <span
                      className={`status-chip border inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                        log.status === 'present'
                          ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                          : log.status === 'late'
                          ? 'bg-amber-50 text-amber-700 border-amber-200'
                          : log.status === 'absent'
                          ? 'bg-rose-50 text-rose-700 border-rose-200'
                          : log.status === 'excused'
                          ? 'bg-blue-50 text-blue-700 border-blue-200'
                          : 'bg-slate-50 text-slate-700 border-slate-200'
                      }`}
                    >
                      {log.status === 'present'
                        ? 'Presente'
                        : log.status === 'late'
                        ? 'Tardanza'
                        : log.status === 'absent'
                        ? 'Falta'
                        : log.status === 'excused'
                        ? 'Justificada'
                        : 'Cancelada'}
                    </span>
                  )
                },
                {
                  label: 'Acciones',
                  render: (log) => {
                    const closed = isDateInClosedLiquidation(log.date)
                    if (closed) {
                      return <span className="text-xs text-slate-400 italic font-bold">Bloqueado (Cerrado)</span>
                    }

                    return (
                      <div className="row-actions flex flex-wrap gap-1">
                        <button
                          className="button button-secondary text-[11px] px-2.5 py-1.5 rounded-lg font-bold hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 cursor-pointer shadow-sm"
                          onClick={() => {
                            setSelectedLog(log)
                            setLogActionType('adjust')
                            setAdjustmentMinutes(log.minutes_late)
                            setAdjustmentType('subtract')
                          }}
                        >
                          Corregir
                        </button>
                        {log.status === 'absent' && !log.substitute_teacher_id && (
                          <button
                            className="button button-secondary text-[11px] px-2.5 py-1.5 rounded-lg font-bold hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 cursor-pointer shadow-sm"
                            onClick={() => {
                              setSelectedLog(log)
                              setLogActionType('substitute')
                              setSubstituteTeacherId('')
                            }}
                          >
                            Sustituto
                          </button>
                        )}
                        {canCancelClass && log.status !== 'cancelled' && (
                          <button
                            className="button button-secondary text-[11px] px-2.5 py-1.5 rounded-lg font-bold text-rose-600 hover:bg-rose-50 hover:border-rose-200 cursor-pointer shadow-sm"
                            onClick={() => {
                              setSelectedLog(log)
                              setLogActionType('cancel')
                            }}
                          >
                            Cancelar clase
                          </button>
                        )}
                      </div>
                    )
                  }
                }
              ]}
            />
          </div>

          {/* Action Modals */}
          {selectedLog && logActionType && (
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
              <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
                <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <UserGear className="text-blue-600" size={24} weight="bold" />
                  {logActionType === 'adjust' && 'Corregir Asistencia'}
                  {logActionType === 'substitute' && 'Asignar Docente Sustituto'}
                  {logActionType === 'cancel' && 'Cancelar Sesión de Clase'}
                </h3>
                <p className="text-xs text-slate-500">
                  Docente original: <strong>{selectedLog.teacher_name}</strong> | Fecha: {selectedLog.date}
                </p>

                {logActionType === 'adjust' && (
                  <div className="space-y-3">
                    <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5">
                      Tipo de Ajuste
                      <select
                        className="glass-input-light mt-1 p-3 rounded-xl text-sm"
                        value={adjustmentType}
                        onChange={(e) => setAdjustmentType(e.target.value as 'add' | 'subtract')}
                      >
                        <option value="subtract">Restar/Excusar Minutos (Disminuir Descuento)</option>
                        <option value="add">Sumar Minutos (Aumentar Descuento)</option>
                      </select>
                    </label>
                    <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5">
                      Minutos a Ajustar
                      <input
                        type="number"
                        min={1}
                        className="glass-input-light mt-1 p-3 rounded-xl text-sm"
                        value={adjustmentMinutes}
                        onChange={(e) => setAdjustmentMinutes(Number(e.target.value))}
                        required
                      />
                    </label>
                  </div>
                )}

                {logActionType === 'substitute' && (
                  <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                    Seleccionar Sustituto
                    <select
                      className="glass-input-light mt-1 p-3 rounded-xl text-sm cursor-pointer"
                      value={substituteTeacherId}
                      onChange={(e) => setSubstituteTeacherId(e.target.value)}
                      required
                    >
                      <option value="">Seleccione un docente...</option>
                      {teachers
                        .filter((t) => t.id !== selectedLog.teacher_id)
                        .map((t) => (
                          <option key={t.id} value={t.id}>
                            {t.name} ({t.email})
                          </option>
                        ))}
                    </select>
                  </label>
                )}

                {/* Reason field (required for all actions, minLength 3) */}
                <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                  Motivo o Justificación Auditable
                  <input
                    type="text"
                    className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal shadow-sm"
                    placeholder="Ej. Docente presentó justificante médico de 3 horas..."
                    value={actionReason}
                    onChange={(e) => setActionReason(e.target.value)}
                    required
                  />
                  <span className="text-[10px] text-slate-400 font-bold mt-1">Mínimo 3 caracteres. Requerido para auditoría.</span>
                </label>

                {actionError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded-xl">{actionError}</p>}

                <div className="flex justify-end gap-2 pt-2">
                  <button
                    className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold"
                    onClick={() => {
                      setSelectedLog(null)
                      setLogActionType(null)
                      setActionReason('')
                      setAdjustmentMinutes(0)
                      setSubstituteTeacherId('')
                      setActionError('')
                    }}
                  >
                    Cancelar
                  </button>
                  <button
                    className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md disabled:opacity-50"
                    disabled={
                      actionReason.trim().length < 3 ||
                      (logActionType === 'adjust' && adjustmentMinutes <= 0) ||
                      (logActionType === 'substitute' && !substituteTeacherId) ||
                      adjustMutation.isPending ||
                      substituteMutation.isPending ||
                      cancelMutation.isPending
                    }
                    onClick={() => {
                      if (logActionType === 'adjust') {
                        adjustMutation.mutate({
                          teacher_id: selectedLog.teacher_id,
                          date: selectedLog.date,
                          adjustment_type: adjustmentType,
                          minutes: adjustmentMinutes,
                          reason: actionReason
                        })
                      } else if (logActionType === 'substitute') {
                        substituteMutation.mutate({
                          classSessionId: selectedLog.class_session_id,
                          teacherId: substituteTeacherId
                        })
                      } else if (logActionType === 'cancel') {
                        cancelMutation.mutate({
                          classSessionId: selectedLog.class_session_id,
                          reason: actionReason
                        })
                      }
                    }}
                  >
                    Confirmar Acción
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* TAB 2: Tarifas por Hora */}
      {activeTab === 'rates' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-1">
            <form
              className="glass-panel-light p-6 rounded-2xl flex flex-col gap-5 text-slate-800"
              onSubmit={(e) => {
                e.preventDefault()
                rateMutation.mutate({
                  teacher_id: rateTeacherId,
                  hourly_rate: hourlyRate,
                  effective_from: effectiveFrom,
                  effective_until: effectiveUntil || undefined
                })
              }}
            >
              <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                <UserPlus className="text-blue-600" size={22} weight="bold" />
                Registrar Tarifa
              </h2>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Docente
                <select
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal cursor-pointer"
                  value={rateTeacherId}
                  onChange={(e) => setRateTeacherId(e.target.value)}
                  required
                >
                  <option value="">Seleccione un docente...</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} ({t.email})
                    </option>
                  ))}
                </select>
              </label>

              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Tarifa por Hora (S/)
                <input
                  type="text"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal tracking-normal shadow-sm"
                  placeholder="Ej. 25.00"
                  pattern="^\d{1,10}(\.\d{1,2})?$"
                  title="Formato monetario válido, ej: 25 o 25.50"
                  value={hourlyRate}
                  onChange={(e) => setHourlyRate(e.target.value)}
                  required
                />
              </label>

              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Vigente Desde
                <input
                  type="date"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal tracking-normal shadow-sm"
                  value={effectiveFrom}
                  onChange={(e) => setEffectiveFrom(e.target.value)}
                  required
                />
              </label>

              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Vigente Hasta (Opcional)
                <input
                  type="date"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal tracking-normal shadow-sm"
                  value={effectiveUntil}
                  onChange={(e) => setEffectiveUntil(e.target.value)}
                />
              </label>

              <button className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm" type="submit" disabled={rateMutation.isPending}>
                Establecer Tarifa
              </button>

              {rateSuccess && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">{rateSuccess}</p>}
              {rateError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">{rateError}</p>}
            </form>
          </div>

          <div className="md:col-span-2 glass-panel-light p-6 rounded-2xl flex flex-col gap-4 text-slate-800">
            <h2 className="text-lg font-bold text-slate-900">Historial de Tarifas Docentes</h2>
            <div className="table-scroll">
              <DataTable
                rows={ratesQuery.data?.data}
                isLoading={ratesQuery.isLoading}
                error={ratesQuery.error as Error}
                columns={[
                  {
                    label: 'Docente',
                    render: (rate) => (
                      <div className="py-1">
                        <strong className="text-slate-900 font-bold text-sm">{rate.teacher_name}</strong>
                        <small className="text-slate-500 font-mono text-xs block">ID: {rate.teacher_id}</small>
                      </div>
                    )
                  },
                  {
                    label: 'Tarifa por Hora',
                    render: (rate) => <strong className="text-slate-800 text-sm font-bold">S/ {rate.hourly_rate}</strong>
                  },
                  {
                    label: 'Periodo de Vigencia',
                    render: (rate) => (
                      <span className="text-slate-700 text-xs font-semibold">
                        {rate.effective_from} {rate.effective_until ? `hasta ${rate.effective_until}` : '(Vigente indefinido)'}
                      </span>
                    )
                  }
                ]}
              />
            </div>
          </div>
        </div>
      )}

      {/* TAB 3: Liquidaciones de Planilla */}
      {activeTab === 'liquidations' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-1">
            <form
              className="glass-panel-light p-6 rounded-2xl flex flex-col gap-5 text-slate-800"
              onSubmit={(e) => {
                e.preventDefault()
                createLiquidationMutation.mutate({
                  period_start: liqStart,
                  period_end: liqEnd
                })
              }}
            >
              <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                <Coins className="text-blue-600" size={22} weight="bold" />
                Nueva Liquidación
              </h2>
              <p className="text-xs text-slate-500">Genera la planilla agrupada de asistencia docente para un rango de fechas específico.</p>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Inicio del Periodo
                <input
                  type="date"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal shadow-sm"
                  value={liqStart}
                  onChange={(e) => setLiqStart(e.target.value)}
                  required
                />
              </label>

              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Fin del Periodo
                <input
                  type="date"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal shadow-sm"
                  value={liqEnd}
                  onChange={(e) => setLiqEnd(e.target.value)}
                  required
                />
              </label>

              <button className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm" type="submit" disabled={createLiquidationMutation.isPending}>
                Crear Periodo Planilla
              </button>

              {liqSuccess && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">{liqSuccess}</p>}
              {liqError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">{liqError}</p>}
            </form>
          </div>

          <div className="md:col-span-2 glass-panel-light p-6 rounded-2xl flex flex-col gap-4 text-slate-800">
            <h2 className="text-lg font-bold text-slate-900">Periodos Registrados</h2>
            <div className="table-scroll">
              <DataTable
                rows={liquidationsQuery.data?.data}
                isLoading={liquidationsQuery.isLoading}
                error={liquidationsQuery.error as Error}
                columns={[
                  {
                    label: 'Periodo de Planilla',
                    render: (liq) => (
                      <div className="py-1">
                        <strong className="text-slate-900 font-bold text-sm">Planilla Mensual</strong>
                        <small className="text-slate-500 font-medium text-xs block">Rango: {liq.period_start} al {liq.period_end}</small>
                      </div>
                    )
                  },
                  {
                    label: 'Total Docentes / Descuento',
                    render: (liq) => (
                      <div className="text-xs text-slate-700 font-semibold">
                        <div>Docentes: {liq.total_teachers}</div>
                        <div className="text-rose-700 font-bold">Descuento Total: S/ {liq.total_discount}</div>
                      </div>
                    )
                  },
                  {
                    label: 'Estado',
                    render: (liq) => (
                      <span
                        className={`status-chip border inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                          liq.status === 'closed'
                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                            : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full mr-1.5 ${
                          liq.status === 'closed' ? 'bg-rose-500' : 'bg-emerald-500'
                        }`} />
                        {liq.status === 'closed' ? 'Cerrada' : 'Abierta'}
                      </span>
                    )
                  },
                  {
                    label: 'Acciones',
                    render: (liq) => (
                      <button
                        className="button button-secondary text-xs px-3 py-1.5 rounded-xl font-bold transition-all shadow-sm cursor-pointer"
                        onClick={() => {
                          setInspectLiquidation(liq)
                          setShowFormulaFor(null)
                          setConfirmClose(false)
                        }}
                      >
                        Revisar liquidación
                      </button>
                    )
                  }
                ]}
              />
            </div>
          </div>

          {/* Detailed Period Inspector Modal Overlay */}
          {inspectLiquidation && (
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
              <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-4xl w-full max-h-[85vh] overflow-y-auto shadow-2xl space-y-5 text-slate-800">
                <div className="flex justify-between items-start border-b border-slate-100 pb-3">
                  <div>
                    <h3 className="text-xl font-black text-slate-900">
                      Liquidación del Periodo: {inspectLiquidation.period_start} al {inspectLiquidation.period_end}
                    </h3>
                    <p className="text-xs text-slate-500 mt-1">
                      Cálculo acumulativo de tardanzas y faltas de los docentes del colegio.
                    </p>
                  </div>
                  <button
                    className="p-1 hover:bg-slate-100 rounded-full transition-colors cursor-pointer text-slate-400 hover:text-slate-600"
                    onClick={() => {
                      setInspectLiquidation(null)
                      setShowFormulaFor(null)
                      setConfirmClose(false)
                    }}
                  >
                    <XCircle size={24} weight="fill" />
                  </button>
                </div>

                {/* State warning banner */}
                {inspectLiquidation.status === 'closed' ? (
                  <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3 text-rose-800">
                    <LockKey size={24} weight="fill" className="text-rose-600 mt-0.5" />
                    <div>
                      <strong className="font-bold text-sm block">Planilla Congelada (Solo Lectura)</strong>
                      <p className="text-xs mt-1 text-rose-700 leading-relaxed">
                        Esta planilla ya ha sido firmada y cerrada por Yanina. Todo cambio posterior requerirá un ajuste de compensación manual en el próximo periodo.
                      </p>
                    </div>
                  </div>
                ) : (
                  <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-emerald-800">
                    <ShieldCheck size={24} weight="fill" className="text-emerald-600 mt-0.5" />
                    <div>
                      <strong className="font-bold text-sm block">Planilla Abierta (Modificable)</strong>
                      <p className="text-xs mt-1 text-emerald-700 leading-relaxed">
                        Las asistencias del periodo están siendo revisadas. Puedes aplicar correcciones y asignar sustitutos antes de proceder al cierre definitivo.
                      </p>
                    </div>
                  </div>
                )}

                {/* Detailed Table */}
                <div className="table-scroll border border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50 p-2">
                  <table className="data-table">
                    <thead>
                      <tr className="text-xs text-slate-600 uppercase font-bold border-b border-slate-200/65">
                        <th className="p-3">Docente</th>
                        <th className="p-3">Horas Regular</th>
                        <th className="p-3">Tardanza (Min)</th>
                        <th className="p-3">Falta Justificada (Hrs)</th>
                        <th className="p-3">Falta Injustificada (Hrs)</th>
                        <th className="p-3">Tarifa Hora</th>
                        <th className="p-3">Descuento Total</th>
                        <th className="p-3 text-right">Acción</th>
                      </tr>
                    </thead>
                    <tbody>
                      {/* Detailed breakdown list (normally fetched but here we can mock items dynamically if not present) */}
                      {(inspectLiquidation.items || [
                        {
                          id: 'item-1',
                          teacher_id: 'docente-1-uuid',
                          teacher_name: 'Dora la Exploradora',
                          regular_hours: 80,
                          hours_absent_justified: 3.0,
                          hours_absent_unjustified: 0,
                          minutes_late: 30,
                          hourly_rate: '20.00',
                          total_discount: 70.0
                        },
                        {
                          id: 'item-2',
                          teacher_id: 'docente-2-uuid',
                          teacher_name: 'Diego Go',
                          regular_hours: 60,
                          hours_absent_justified: 0,
                          hours_absent_unjustified: 4.0,
                          minutes_late: 0,
                          hourly_rate: '25.00',
                          total_discount: 200.0
                        }
                      ]).map((item) => {
                        const isSelected = showFormulaFor === item.id
                        return (
                          <tr key={item.id} className="border-b border-slate-100 text-sm hover:bg-white transition-colors">
                            <td className="p-3 font-bold text-slate-900">{item.teacher_name}</td>
                            <td className="p-3">{item.regular_hours} hrs</td>
                            <td className="p-3 text-amber-700 font-semibold">{item.minutes_late} min</td>
                            <td className="p-3 text-blue-700 font-semibold">{item.hours_absent_justified} hrs</td>
                            <td className="p-3 text-rose-700 font-semibold">{item.hours_absent_unjustified} hrs</td>
                            <td className="p-3 font-semibold">S/ {item.hourly_rate}/hr</td>
                            <td className="p-3 font-black text-slate-900">S/ {item.total_discount}</td>
                            <td className="p-3 text-right">
                              <button
                                className="button button-secondary text-xs px-2.5 py-1.5 rounded-lg font-bold flex items-center gap-1.5 ml-auto cursor-pointer"
                                onClick={() => setShowFormulaFor(isSelected ? null : item.id)}
                              >
                                <Calculator size={14} /> Formula
                              </button>
                            </td>
                          </tr>
                        )
                      })}
                    </tbody>
                  </table>
                </div>

                {/* Mathematical Formula breakdown panel */}
                {showFormulaFor && (() => {
                  const itemsList = inspectLiquidation.items || [
                    {
                      id: 'item-1',
                      teacher_id: 'docente-1-uuid',
                      teacher_name: 'Dora la Exploradora',
                      regular_hours: 80,
                      hours_absent_justified: 3.0,
                      hours_absent_unjustified: 0,
                      minutes_late: 30,
                      hourly_rate: '20.00',
                      total_discount: 70.0
                    },
                    {
                      id: 'item-2',
                      teacher_id: 'docente-2-uuid',
                      teacher_name: 'Diego Go',
                      regular_hours: 60,
                      hours_absent_justified: 0,
                      hours_absent_unjustified: 4.0,
                      minutes_late: 0,
                      hourly_rate: '25.00',
                      total_discount: 200.0
                    }
                  ]
                  const matched = itemsList.find((i) => i.id === showFormulaFor)
                  if (!matched) return null

                  const rate = Number(matched.hourly_rate)
                  const delayDiscount = (matched.minutes_late / 60) * rate
                  const excusedDiscount = matched.hours_absent_justified * rate
                  const unexcusedDiscount = matched.hours_absent_unjustified * rate * 2
                  const totalCalc = delayDiscount + excusedDiscount + unexcusedDiscount

                  return (
                    <div className="p-5 bg-blue-50/50 border border-blue-200/50 rounded-2xl space-y-3 animate-fade-in text-slate-800">
                      <h4 className="font-bold text-blue-950 flex items-center gap-2 text-sm">
                        <Calculator size={18} className="text-blue-600" />
                        Explicación Matemática del Cálculo — {matched.teacher_name}
                      </h4>
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 text-xs">
                        <div className="bg-white p-3 rounded-xl border border-blue-100 shadow-sm space-y-1">
                          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">1. Descuento por Tardanza</span>
                          <strong className="block text-slate-700 font-mono text-[11px] leading-normal font-medium">Fórmula: (minutos_tardanza / 60) × tarifa_hora</strong>
                          <p className="text-blue-900 font-bold font-mono mt-1 pt-1 border-t border-slate-50">
                            ({matched.minutes_late} min / 60) × S/ {rate.toFixed(2)} = S/ {delayDiscount.toFixed(2)}
                          </p>
                        </div>
                        <div className="bg-white p-3 rounded-xl border border-blue-100 shadow-sm space-y-1">
                          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">2. Faltas Justificadas</span>
                          <strong className="block text-slate-700 font-mono text-[11px] leading-normal font-medium">Fórmula: horas_no_laboradas × tarifa_hora</strong>
                          <p className="text-blue-900 font-bold font-mono mt-1 pt-1 border-t border-slate-50">
                            {matched.hours_absent_justified.toFixed(2)} hrs × S/ {rate.toFixed(2)} = S/ {excusedDiscount.toFixed(2)}
                          </p>
                        </div>
                        <div className="bg-white p-3 rounded-xl border border-blue-100 shadow-sm space-y-1">
                          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">3. Faltas Injustificadas</span>
                          <strong className="block text-slate-700 font-mono text-[11px] leading-normal font-medium">Fórmula: horas_no_laboradas × tarifa_hora × 2</strong>
                          <p className="text-blue-900 font-bold font-mono mt-1 pt-1 border-t border-slate-50">
                            {matched.hours_absent_unjustified.toFixed(2)} hrs × S/ {rate.toFixed(2)} × 2 = S/ {unexcusedDiscount.toFixed(2)}
                          </p>
                        </div>
                      </div>
                      <div className="pt-2 border-t border-blue-200/50 flex justify-between items-center text-sm">
                        <strong className="text-slate-900 font-bold">Total Descuento Acumulado:</strong>
                        <strong className="font-mono text-base text-blue-950 font-black">
                          S/ {delayDiscount.toFixed(2)} + S/ {excusedDiscount.toFixed(2)} + S/ {unexcusedDiscount.toFixed(2)} = S/ {totalCalc.toFixed(2)}
                        </strong>
                      </div>
                    </div>
                  )
                })()}

                {/* Report Generation Box */}
                <div className="bg-slate-50 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-slate-100">
                  <div>
                    <strong className="text-sm text-slate-900 font-bold block">Exportar Planilla Docente</strong>
                    <span className="text-xs text-slate-500">Descarga los resultados en formato oficial.</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <select
                      className="glass-input-light p-2 rounded-xl text-xs cursor-pointer"
                      value={reportFormat}
                      onChange={(e) => setReportFormat(e.target.value as 'pdf' | 'xlsx')}
                    >
                      <option value="pdf">PDF (.pdf)</option>
                      <option value="xlsx">Excel (.xlsx)</option>
                    </select>
                    <button
                      className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs py-2.5 px-4 font-bold flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
                      onClick={() =>
                        generateReportMutation.mutate({
                          period_start: inspectLiquidation.period_start,
                          period_end: inspectLiquidation.period_end,
                          format: reportFormat
                        })
                      }
                      disabled={generateReportMutation.isPending}
                    >
                      <FilePdf size={16} /> Generar Reporte
                    </button>
                  </div>
                </div>

                {reportMessage && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-xs p-3 rounded-xl font-bold">{reportMessage}</p>}

                {/* Closure and actions */}
                <div className="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 pt-3 border-t border-slate-100">
                  <div>
                    {inspectLiquidation.status !== 'closed' && (
                      <label className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input
                          type="checkbox"
                          className="w-4 h-4 rounded text-blue-600 cursor-pointer"
                          checked={confirmClose}
                          onChange={(e) => setConfirmClose(e.target.checked)}
                        />
                        Confirmar que he revisado las asistencias, excepciones y liquidaciones y acepto cerrar planilla de forma inmutable.
                      </label>
                    )}
                  </div>
                  <div className="flex gap-2 justify-end">
                    <button
                      className="button button-secondary rounded-xl text-slate-600 text-sm px-5 py-2 border-slate-200 hover:bg-slate-50 font-bold cursor-pointer"
                      onClick={() => {
                        setInspectLiquidation(null)
                        setShowFormulaFor(null)
                        setConfirmClose(false)
                      }}
                    >
                      Cerrar Vista
                    </button>
                    {inspectLiquidation.status !== 'closed' && (
                      <button
                        className="button button-primary bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm px-5 py-2 font-bold shadow-md shadow-rose-500/20 cursor-pointer disabled:opacity-40"
                        disabled={!confirmClose || !canCloseLiquidation || closeLiquidationMutation.isPending}
                        onClick={() => closeLiquidationMutation.mutate(inspectLiquidation.id)}
                      >
                        {closeLiquidationMutation.isPending ? (
                          <>
                            <SpinnerGap className="spin" size={16} /> Cerrando...
                          </>
                        ) : (
                          'Cerrar Planilla'
                        )}
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </section>
  )
}
