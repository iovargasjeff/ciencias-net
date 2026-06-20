import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Clock,
  Calendar,
  User,
  MagnifyingGlass,
  Check,
  X,
  ArrowBendDownRight,
  Warning,
  SpinnerGap,
  ArrowClockwise,
  FileText
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { listAcademic } from '@/features/phase-one/api'
import { DataTable } from '@/components/shared/DataTable'
import { OperationalState } from '@/components/shared/OperationalState'
import { getApiError } from '@/lib/api/client'
import {
  listStudentAttendance,
  createManualStudentAttendanceEvent,
  closeStudentAttendanceDay,
  listStudentAttendanceAnomalies,
  resolveStudentAttendanceAnomaly,
  justifyStudentAbsence,
  listRecognitionEventsForReview,
  reviewRecognitionEvent,
  deleteStudentAttendance
} from './api'
import type { StudentAttendance, StudentAttendanceAnomaly, RecognitionEvent } from './types'

export function StudentAttendancePage() {
  const { user } = useAuth()
  const client = useQueryClient()

  // 1. Authorization
  const hasAccess = user?.roles.some((role) => ['superadmin', 'auxiliar', 'toe'].includes(role))
  const isToeOnly = user?.roles.includes('toe') && !user?.roles.some((r) => ['superadmin', 'auxiliar'].includes(r))

  // 2. Tab Navigation: 'general' | 'anomalies' | 'closure'
  const [activeTab, setActiveTab] = useState<'general' | 'anomalies' | 'closure'>('general')

  // 3. Search & Filters State
  const [dateFilter, setDateFilter] = useState(new Date().toISOString().slice(0, 10))
  const [gradeFilter, setGradeFilter] = useState('')
  const [sectionFilter, setSectionFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [searchFilter, setSearchFilter] = useState('')

  // 4. Modals State
  const [showManualModal, setShowManualModal] = useState(false)
  const [showJustifyModal, setShowJustifyModal] = useState(false)
  const [showResolveAnomalyModal, setShowResolveAnomalyModal] = useState(false)
  const [showReviewModal, setShowReviewModal] = useState(false)

  // Selected records for action
  const [selectedRecord, setSelectedRecord] = useState<StudentAttendance | null>(null)
  const [selectedAnomaly, setSelectedAnomaly] = useState<StudentAttendanceAnomaly | null>(null)
  const [selectedEvent, setSelectedEvent] = useState<RecognitionEvent | null>(null)

  // Modals form input states
  // Manual Event Form
  const [manualStudentId, setManualStudentId] = useState('')
  const [manualEventType, setManualEventType] = useState<'entry' | 'exit' | 'absence' | 'late'>('entry')
  const [manualOccurredAt, setManualOccurredAt] = useState('')
  const [manualReason, setManualReason] = useState('')
  const [manualError, setManualError] = useState('')

  // Justify Absence Form
  const [justifyReason, setJustifyReason] = useState('')
  const [justifyError, setJustifyError] = useState('')

  // Resolve Anomaly Form
  const [anomalyExitTime, setAnomalyExitTime] = useState('')
  const [anomalyReason, setAnomalyReason] = useState('')
  const [anomalyError, setAnomalyError] = useState('')

  // Review Recognition Form
  const [reviewOutcome, setReviewOutcome] = useState<'confirmed' | 'rejected' | 'reassigned'>('confirmed')
  const [reviewReason, setReviewReason] = useState('')
  const [reviewMatchedStudentId, setReviewMatchedStudentId] = useState('')
  const [reviewError, setReviewError] = useState('')

  // Closure Form
  const [closureDate, setClosureDate] = useState(new Date().toISOString().slice(0, 10))
  const [closureMessage, setClosureMessage] = useState('')
  const [closureError, setClosureError] = useState('')

  // 5. Query Queries
  const gradesQuery = useQuery({
    queryKey: ['academic', 'grades'],
    queryFn: () => listAcademic('grades'),
    enabled: hasAccess
  })

  const sectionsQuery = useQuery({
    queryKey: ['academic', 'sections'],
    queryFn: () => listAcademic('sections'),
    enabled: hasAccess
  })

  const attendanceQuery = useQuery({
    queryKey: [
      'attendance',
      { date: dateFilter, grade: gradeFilter, section: sectionFilter, status: statusFilter, search: searchFilter }
    ],
    queryFn: () =>
      listStudentAttendance({
        date: dateFilter || undefined,
        grade: gradeFilter || undefined,
        section: sectionFilter || undefined,
        status: statusFilter || undefined,
        search: searchFilter || undefined
      }),
    enabled: hasAccess
  })

  const anomaliesQuery = useQuery({
    queryKey: ['anomalies'],
    queryFn: listStudentAttendanceAnomalies,
    enabled: hasAccess
  })

  const recognitionEventsQuery = useQuery({
    queryKey: ['recognitionEvents'],
    queryFn: listRecognitionEventsForReview,
    enabled: hasAccess
  })

  // Invalidation helpers
  const invalidateAttendance = () => client.invalidateQueries({ queryKey: ['attendance'] })
  const invalidateAnomalies = () => client.invalidateQueries({ queryKey: ['anomalies'] })
  const invalidateRecognition = () => client.invalidateQueries({ queryKey: ['recognitionEvents'] })

  // 6. Mutations
  const createManualEventMutation = useMutation({
    mutationFn: createManualStudentAttendanceEvent,
    onSuccess: async () => {
      setShowManualModal(false)
      setManualStudentId('')
      setManualOccurredAt('')
      setManualReason('')
      setManualError('')
      await invalidateAttendance()
      await invalidateAnomalies()
    },
    onError: (err) => {
      setManualError(getApiError(err).message)
    }
  })

  const justifyAbsenceMutation = useMutation({
    mutationFn: ({ attendanceId, reason }: { attendanceId: string; reason: string }) =>
      justifyStudentAbsence(attendanceId, reason),
    onSuccess: async () => {
      setShowJustifyModal(false)
      setSelectedRecord(null)
      setJustifyReason('')
      setJustifyError('')
      await invalidateAttendance()
    },
    onError: (err) => {
      setJustifyError(getApiError(err).message)
    }
  })

  const resolveAnomalyMutation = useMutation({
    mutationFn: async ({
      anomalyId,
      studentId,
      exitTime,
      reason
    }: {
      anomalyId: string
      studentId: string
      exitTime: string
      reason: string
    }) => {
      // 1. Create a manual exit event as required
      await createManualStudentAttendanceEvent({
        student_id: studentId,
        event_type: 'exit',
        occurred_at: exitTime,
        reason: reason
      })
      // 2. Resolve the anomaly
      return resolveStudentAttendanceAnomaly(anomalyId, reason)
    },
    onSuccess: async () => {
      setShowResolveAnomalyModal(false)
      setSelectedAnomaly(null)
      setAnomalyExitTime('')
      setAnomalyReason('')
      setAnomalyError('')
      await invalidateAnomalies()
      await invalidateAttendance()
    },
    onError: (err) => {
      setAnomalyError(getApiError(err).message)
    }
  })

  const reviewRecognitionMutation = useMutation({
    mutationFn: ({
      eventId,
      outcome,
      reason,
      matchedStudentId
    }: {
      eventId: string
      outcome: 'confirmed' | 'rejected' | 'reassigned'
      reason: string
      matchedStudentId?: string
    }) =>
      reviewRecognitionEvent(eventId, {
        outcome,
        reason,
        matched_student_id: outcome === 'reassigned' ? matchedStudentId : undefined
      }),
    onSuccess: async () => {
      setShowReviewModal(false)
      setSelectedEvent(null)
      setReviewReason('')
      setReviewMatchedStudentId('')
      setReviewError('')
      await invalidateRecognition()
      await invalidateAttendance()
      await invalidateAnomalies()
    },
    onError: (err) => {
      setReviewError(getApiError(err).message)
    }
  })

  const closeDayMutation = useMutation({
    mutationFn: closeStudentAttendanceDay,
    onSuccess: () => {
      setClosureMessage('Jornada escolar cerrada con éxito para la fecha indicada.')
      setClosureError('')
      invalidateAttendance()
    },
    onError: (err) => {
      setClosureMessage('')
      setClosureError(getApiError(err).message)
    }
  })

  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [deleteReason, setDeleteReason] = useState('')
  const [deleteError, setDeleteError] = useState('')

  const deleteAttendanceMutation = useMutation({
    mutationFn: ({ attendanceId, reason }: { attendanceId: string; reason: string }) =>
      deleteStudentAttendance(attendanceId, reason),
    onSuccess: async () => {
      setShowDeleteModal(false)
      setSelectedRecord(null)
      setDeleteReason('')
      setDeleteError('')
      await invalidateAttendance()
    },
    onError: (err) => {
      setDeleteError(getApiError(err).message)
    }
  })

  // 7. Forbidden view Check
  if (!hasAccess) {
    return (
      <div className="p-6">
        <OperationalState
          state="forbidden"
          title="Sin permiso"
          message="No tienes autorización para acceder a la supervisión de asistencia de alumnos."
        />
      </div>
    )
  }

  return (
    <section className="page-stack space-y-6 max-w-7xl mx-auto p-4 md:p-6" aria-label="Supervisión de asistencia">
      {/* Header */}
      <header className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5">
        <div className="space-y-1">
          <p className="eyebrow text-xs font-bold text-blue-600 uppercase tracking-wider">Supervisión</p>
          <h1 className="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Clock className="text-blue-600" size={32} weight="duotone" />
            Asistencia de Alumnos
          </h1>
          <p className="text-sm text-slate-500 font-medium">
            Permite a los auxiliares registrar excepciones, validar rostros y realizar cierres diarios.
          </p>
        </div>

        {/* Action Button: Manual Event (Only for superadmin & auxiliar) */}
        {!isToeOnly && (
          <button
            className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2.5 font-bold shadow-md shadow-blue-500/10 hover:shadow-blue-500/25 transition-all text-sm flex items-center gap-2 self-start md:self-auto"
            onClick={() => {
              setManualError('')
              setShowManualModal(true)
            }}
          >
            <Clock size={18} weight="bold" />
            Registrar Evento Manual
          </button>
        )}
      </header>

      {/* Navigation Tabs */}
      <div className="flex bg-slate-100/80 p-1 rounded-2xl max-w-md border border-slate-200/50 shadow-sm" role="tablist">
        <button
          role="tab"
          aria-selected={activeTab === 'general'}
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center ${
            activeTab === 'general'
              ? 'bg-white text-blue-600 shadow-sm'
              : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
          }`}
          onClick={() => setActiveTab('general')}
        >
          Asistencia General
        </button>
        <button
          role="tab"
          aria-selected={activeTab === 'anomalies'}
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center flex items-center justify-center gap-1.5 ${
            activeTab === 'anomalies'
              ? 'bg-white text-blue-600 shadow-sm'
              : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
          }`}
          onClick={() => setActiveTab('anomalies')}
        >
          Incidencias y Rostros
          {(anomaliesQuery.data?.data.length || 0) + (recognitionEventsQuery.data?.data.length || 0) > 0 && (
            <span className="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.5 rounded-full font-bold border border-amber-200">
              {(anomaliesQuery.data?.data.length || 0) + (recognitionEventsQuery.data?.data.length || 0)}
            </span>
          )}
        </button>
        {!isToeOnly && (
          <button
            role="tab"
            aria-selected={activeTab === 'closure'}
            className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center ${
              activeTab === 'closure'
                ? 'bg-white text-blue-600 shadow-sm'
                : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
            }`}
            onClick={() => setActiveTab('closure')}
          >
            Cierre de Jornada
          </button>
        )}
      </div>

      {/* TAB 1: Asistencia General */}
      {activeTab === 'general' && (
        <div className="space-y-6">
          {/* Filters Bar */}
          <div className="glass-panel-light p-4 rounded-2xl border border-slate-100 bg-white shadow-sm flex flex-wrap gap-4 items-end">
            <div className="flex-1 min-w-[200px] space-y-1">
              <label htmlFor="filter-search" className="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1 cursor-pointer">
                <MagnifyingGlass size={12} /> Buscar Alumno
              </label>
              <input
                id="filter-search"
                type="text"
                className="glass-input-light w-full p-2.5 rounded-xl text-sm font-normal shadow-sm border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                placeholder="Nombre o ID..."
                value={searchFilter}
                onChange={(e) => setSearchFilter(e.target.value)}
              />
            </div>

            <div className="w-full sm:w-auto min-w-[130px] space-y-1">
              <label htmlFor="filter-date" className="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1 cursor-pointer">
                <Calendar size={12} /> Fecha
              </label>
              <input
                id="filter-date"
                type="date"
                className="glass-input-light w-full p-2.5 rounded-xl text-sm font-normal shadow-sm border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none cursor-pointer"
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
              />
            </div>

            <div className="w-full sm:w-auto min-w-[120px] space-y-1">
              <label htmlFor="filter-grade" className="text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer">
                Grado
              </label>
              <select
                id="filter-grade"
                className="glass-input-light w-full p-2.5 rounded-xl text-sm font-normal shadow-sm border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none cursor-pointer"
                value={gradeFilter}
                onChange={(e) => setGradeFilter(e.target.value)}
              >
                <option value="">Todos</option>
                {gradesQuery.data?.data.map((g) => (
                  <option key={g.id} value={g.id}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="w-full sm:w-auto min-w-[120px] space-y-1">
              <label htmlFor="filter-section" className="text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer">
                Sección
              </label>
              <select
                id="filter-section"
                className="glass-input-light w-full p-2.5 rounded-xl text-sm font-normal shadow-sm border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none cursor-pointer"
                value={sectionFilter}
                onChange={(e) => setSectionFilter(e.target.value)}
              >
                <option value="">Todas</option>
                {sectionsQuery.data?.data
                  .filter((sec) => !gradeFilter || sec.grade_id === gradeFilter)
                  .map((sec) => (
                    <option key={sec.id} value={sec.id}>
                      {sec.name}
                    </option>
                  ))}
              </select>
            </div>

            <div className="w-full sm:w-auto min-w-[120px] space-y-1">
              <label htmlFor="filter-status" className="text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer">
                Estado
              </label>
              <select
                id="filter-status"
                className="glass-input-light w-full p-2.5 rounded-xl text-sm font-normal shadow-sm border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none cursor-pointer"
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <option value="">Todos</option>
                <option value="present">Puntual / Presente</option>
                <option value="late">Tardanza</option>
                <option value="absent">Falta Injustificada</option>
                <option value="excused">Falta Justificada</option>
              </select>
            </div>

            <button
              className="button button-secondary p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 flex items-center justify-center shadow-sm"
              title="Actualizar tabla"
              onClick={() => invalidateAttendance()}
            >
              <ArrowClockwise size={18} />
            </button>
          </div>

          {/* Records Table */}
          <div className="glass-panel-light p-6 rounded-2xl border border-slate-100 bg-white shadow-sm space-y-4">
            <h2 className="text-lg font-bold text-slate-900">Listado de Asistencia</h2>
            <DataTable
              rows={attendanceQuery.data?.data}
              isLoading={attendanceQuery.isLoading}
              error={attendanceQuery.error as Error}
              columns={[
                {
                  label: 'Estudiante',
                  render: (record) => (
                    <div className="space-y-1 py-1">
                      <strong className="text-slate-900 font-bold text-sm block">{record.student_name}</strong>
                      <span className="text-[10px] font-mono text-slate-500 block">Ref. {record.student_id}</span>
                    </div>
                  )
                },
                {
                  label: 'Aula',
                  render: (record) => (
                    <span className="text-slate-600 font-medium text-sm">
                      {record.grade} - {record.section}
                    </span>
                  )
                },
                {
                  label: 'Estado',
                  render: (record) => {
                    const styles: Record<string, string> = {
                      present: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                      late: 'bg-amber-50 text-amber-700 border-amber-200',
                      absent: 'bg-rose-50 text-rose-700 border-rose-200',
                      excused: 'bg-blue-50 text-blue-700 border-blue-200'
                    }
                    const labels: Record<string, string> = {
                      present: 'Puntual',
                      late: 'Tardanza',
                      absent: 'Falta',
                      excused: 'Justificado'
                    }
                    return (
                      <span
                        className={`status-chip border inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ${
                          styles[record.status] || 'bg-slate-50 text-slate-700'
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full mr-1.5 ${
                          record.status === 'present' ? 'bg-emerald-500' : record.status === 'late' ? 'bg-amber-500' : record.status === 'absent' ? 'bg-rose-500' : 'bg-blue-500'
                        }`} />
                        {labels[record.status] || record.status}
                      </span>
                    )
                  }
                },
                {
                  label: 'Horas (Entrada / Salida)',
                  render: (record) => (
                    <div className="text-xs space-y-0.5 text-slate-600">
                      <div>
                        <span className="font-semibold text-slate-500">Entrada:</span>{' '}
                        {record.entry_time ? new Date(record.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-'}
                      </div>
                      <div>
                        <span className="font-semibold text-slate-500">Salida:</span>{' '}
                        {record.exit_time ? new Date(record.exit_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-'}
                      </div>
                    </div>
                  )
                },
                {
                  label: 'Historial Auditado / Motivo',
                  render: (record) => (
                    <div className="max-w-[200px] text-xs">
                      {record.justified ? (
                        <div className="space-y-1">
                          <span className="text-blue-800 font-bold bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded text-[10px]">Justificada</span>
                          <p className="text-slate-600 italic truncate" title={record.justification_reason || ''}>
                            {record.justification_reason}
                          </p>
                        </div>
                      ) : record.reason ? (
                        <p className="text-slate-600 font-medium truncate" title={record.reason}>
                          {record.reason}
                        </p>
                      ) : (
                        <span className="text-slate-500 italic">S/N</span>
                      )}
                    </div>
                  )
                },
                {
                  label: 'Acciones',
                  render: (record) => (
                    <div className="flex gap-2 flex-wrap">
                      {/* Justify button (Only visible if status is absent/excused and not justified already) */}
                      {record.status === 'absent' && !record.justified && (
                        <button
                          className="button button-secondary text-blue-600 border-blue-200 hover:bg-blue-50 text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1"
                          onClick={() => {
                            setSelectedRecord(record)
                            setJustifyReason('')
                            setJustifyError('')
                            setShowJustifyModal(true)
                          }}
                        >
                          <FileText size={14} />
                          Justificar
                        </button>
                      )}
                      {record.status === 'present' && !record.exit_time && !isToeOnly && (
                        <button
                          className="button button-secondary text-amber-700 border-amber-200 hover:bg-amber-50 text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm"
                          onClick={() => {
                            // Convert standard record to anomaly format to resolve
                            const mockAnomaly: StudentAttendanceAnomaly = {
                              id: record.id,
                              student_id: record.student_id,
                              student_name: record.student_name,
                              grade: record.grade,
                              section: record.section,
                              date: record.date,
                              entry_time: record.entry_time || '',
                              exit_time: null,
                              status: 'pending'
                            }
                            setSelectedAnomaly(mockAnomaly)
                            setAnomalyExitTime('')
                            setAnomalyReason('')
                            setShowResolveAnomalyModal(true)
                          }}
                        >
                          Forzar Salida
                        </button>
                      )}
                      {/* Delete button (Soft delete) */}
                      {!isToeOnly && record.status !== 'anulada' && (
                        <button
                          className="button button-secondary text-rose-600 border-rose-200 hover:bg-rose-50 text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1"
                          onClick={() => {
                            setSelectedRecord(record)
                            setDeleteReason('')
                            setDeleteError('')
                            setShowDeleteModal(true)
                          }}
                        >
                          <X size={14} />
                          Anular
                        </button>
                      )}
                      {!record.exit_time && record.status !== 'absent' && record.status !== 'excused' && (
                        <span className="text-slate-500 text-xs italic font-medium">En curso</span>
                      )}
                    </div>
                  )
                }
              ]}
            />
          </div>
        </div>
      )}

      {/* TAB 2: Reconocimientos y Anomalías */}
      {activeTab === 'anomalies' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Column 1: Reconocimientos Dudosos */}
          <div className="glass-panel-light p-6 rounded-2xl border border-slate-100 bg-white shadow-sm space-y-4">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                Reconocimientos Dudosos
              </h2>
              <button
                className="text-xs text-blue-600 font-bold flex items-center gap-1 hover:underline"
                onClick={() => invalidateRecognition()}
              >
                <ArrowClockwise size={14} />
                Actualizar
              </button>
            </div>

            {recognitionEventsQuery.isLoading ? (
              <div className="flex items-center justify-center p-12">
                <SpinnerGap className="spin text-blue-600" size={32} />
              </div>
            ) : recognitionEventsQuery.error ? (
              <div className="p-4 text-sm bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                Error: {(recognitionEventsQuery.error as Error).message}
              </div>
            ) : recognitionEventsQuery.data?.data && recognitionEventsQuery.data.data.length > 0 ? (
              <div className="space-y-4">
                {recognitionEventsQuery.data.data.map((evt) => (
                  <div
                    key={evt.id}
                    className="border border-slate-100 rounded-xl p-4 bg-slate-50/50 hover:bg-slate-50 transition-all flex flex-col sm:flex-row gap-4"
                  >
                    {/* Visual container simulating photo capture slot */}
                    <div className="w-full sm:w-28 h-28 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center relative overflow-hidden flex-shrink-0">
                      {evt.image_url ? (
                        <img src={evt.image_url} alt="Rostro capturado" className="object-cover w-full h-full" />
                      ) : (
                        <div className="flex flex-col items-center justify-center text-slate-500">
                          <User size={32} weight="light" />
                          <span className="text-[10px] mt-1 font-bold">Rostro</span>
                        </div>
                      )}
                      <div className="absolute bottom-1 right-1 bg-amber-500 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded shadow-sm">
                        {evt.confidence}% Conf.
                      </div>
                    </div>

                    <div className="flex-1 flex flex-col justify-between">
                      <div className="space-y-1">
                        <span className="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">
                          Estación: {evt.station_name}
                        </span>
                        <strong className="text-slate-900 text-sm font-bold block">
                          Coincidencia: {evt.matched_student_name || 'Desconocido'}
                        </strong>
                        <span className="text-[10px] font-mono text-slate-500 block">{evt.matched_student_id}</span>
                        <span className="text-slate-500 text-xs block font-medium">
                          Hora: {new Date(evt.captured_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </span>
                      </div>

                      <div className="flex gap-1.5 mt-3 sm:mt-0">
                        <button
                          className="button bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1 transition-colors"
                          onClick={() => {
                            setSelectedEvent(evt)
                            setReviewOutcome('confirmed')
                            setReviewReason('')
                            setReviewError('')
                            setShowReviewModal(true)
                          }}
                        >
                          <Check size={14} weight="bold" />
                          Confirmar
                        </button>
                        <button
                          className="button bg-rose-600 hover:bg-rose-700 text-white text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1 transition-colors"
                          onClick={() => {
                            setSelectedEvent(evt)
                            setReviewOutcome('rejected')
                            setReviewReason('')
                            setReviewError('')
                            setShowReviewModal(true)
                          }}
                        >
                          <X size={14} weight="bold" />
                          Rechazar
                        </button>
                        <button
                          className="button bg-blue-600 hover:bg-blue-700 text-white text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1 transition-colors"
                          onClick={() => {
                            setSelectedEvent(evt)
                            setReviewOutcome('reassigned')
                            setReviewMatchedStudentId('')
                            setReviewReason('')
                            setReviewError('')
                            setShowReviewModal(true)
                          }}
                        >
                          <ArrowBendDownRight size={14} weight="bold" />
                          Reasignar
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-slate-500 italic">No hay reconocimientos dudosos pendientes.</div>
            )}
          </div>

          {/* Column 2: Anomalías de Entrada (Ingresos sin Salida) */}
          <div className="glass-panel-light p-6 rounded-2xl border border-slate-100 bg-white shadow-sm space-y-4">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                Ingresos sin Salida (Anomalías)
              </h2>
              <button
                className="text-xs text-blue-600 font-bold flex items-center gap-1 hover:underline"
                onClick={() => invalidateAnomalies()}
              >
                <ArrowClockwise size={14} />
                Actualizar
              </button>
            </div>

            {anomaliesQuery.isLoading ? (
              <div className="flex items-center justify-center p-12">
                <SpinnerGap className="spin text-blue-600" size={32} />
              </div>
            ) : anomaliesQuery.error ? (
              <div className="p-4 text-sm bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                Error: {(anomaliesQuery.error as Error).message}
              </div>
            ) : anomaliesQuery.data?.data && anomaliesQuery.data.data.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {anomaliesQuery.data.data.map((an) => (
                  <div
                    key={an.id}
                    className="bg-white border border-slate-100 hover:border-amber-200 rounded-xl p-4 shadow-sm flex flex-col justify-between gap-4 transition-all"
                  >
                    <div className="space-y-1">
                      <div className="flex justify-between items-start">
                        <strong className="text-slate-900 text-sm font-bold block">{an.student_name}</strong>
                        <span className="text-[10px] bg-amber-50 text-amber-800 border border-amber-100 font-extrabold px-2 py-0.5 rounded-md">
                          Entrada Registrada
                        </span>
                      </div>
                      <span className="text-[10px] font-mono text-slate-500 block">{an.student_id}</span>
                      <p className="text-slate-500 text-xs font-semibold">
                        {an.grade} - {an.section}
                      </p>
                      <div className="text-[11px] text-slate-500 mt-2 bg-slate-50 p-2 rounded-lg border border-slate-100/50">
                        <strong className="block text-slate-700">Ingreso:</strong>
                        {new Date(an.entry_time).toLocaleString()}
                      </div>
                    </div>

                    <div className="pt-2 border-t border-slate-100 flex justify-between items-center">
                      <span className="text-[10px] text-rose-600 font-extrabold flex items-center gap-1">
                        <Warning size={14} weight="fill" />
                        Falta Salida
                      </span>
                      <button
                        className="button button-primary bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg font-bold shadow-sm"
                        onClick={() => {
                          setSelectedAnomaly(an)
                          setAnomalyExitTime('')
                          setAnomalyReason('')
                          setAnomalyError('')
                          setShowResolveAnomalyModal(true)
                        }}
                      >
                        Resolver
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-slate-500 italic">No hay anomalías de entrada registradas.</div>
            )}
          </div>
        </div>
      )}

      {/* TAB 3: Cierre de Jornada (Only superadmin & auxiliar) */}
      {activeTab === 'closure' && !isToeOnly && (
        <div className="max-w-md mx-auto glass-panel-light p-6 rounded-2xl border border-slate-100 bg-white shadow-sm space-y-5">
          <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
            Cierre de Jornada Escolar
          </h2>
          <p className="text-sm text-slate-600 leading-relaxed">
            Esta acción realiza el cierre de asistencia para la fecha indicada, generando automáticamente faltas para aquellos alumnos que no registraron ingreso.
          </p>

          <label htmlFor="closure-date" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
            Fecha de Jornada a Cerrar
            <input
              id="closure-date"
              type="date"
              className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm border border-slate-200"
              value={closureDate}
              onChange={(e) => setClosureDate(e.target.value)}
            />
          </label>

          <button
            className="button button-primary w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm flex items-center justify-center gap-2"
            disabled={closeDayMutation.isPending}
            onClick={() =>
              closeDayMutation.mutate({
                date: closureDate
              })
            }
          >
            {closeDayMutation.isPending ? (
              <SpinnerGap className="spin" size={18} />
            ) : (
              'Ejecutar Cierre de Jornada'
            )}
          </button>

          {closureMessage && (
            <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">
              {closureMessage}
            </p>
          )}
          {closureError && (
            <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
              {closureError}
            </p>
          )}
        </div>
      )}

      {/* MODAL 1: Registrar Evento Manual (Only superadmin & auxiliar) */}
      {showManualModal && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
              Registrar Evento de Asistencia Manual
            </h3>
            <p className="text-xs text-slate-500">
              Registra entradas manuales, salidas, ausencias o tardanzas justificadas.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                createManualEventMutation.mutate({
                  student_id: manualStudentId,
                  event_type: manualEventType,
                  occurred_at: new Date(manualOccurredAt).toISOString(),
                  reason: manualReason
                })
              }}
              className="space-y-4"
            >
              <label htmlFor="manual-student-id" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Alumno por DNI o codigo interno
                <input
                  id="manual-student-id"
                  type="text"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200"
                  placeholder="Ej. 00000000-0000-0000-0000-000000000000"
                  value={manualStudentId}
                  onChange={(e) => setManualStudentId(e.target.value)}
                  required
                />
              </label>

              <label htmlFor="manual-event-type" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Tipo de Evento
                <select
                  id="manual-event-type"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 cursor-pointer"
                  value={manualEventType}
                  onChange={(e) => setManualEventType(e.target.value as 'entry' | 'exit' | 'absence' | 'late')}
                >
                  <option value="entry">Entrada</option>
                  <option value="exit">Salida de Emergencia / Común</option>
                  <option value="absence">Falta Injustificada</option>
                  <option value="late">Tardanza</option>
                </select>
              </label>

              <label htmlFor="manual-occurred-at" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Hora y Fecha Real Ocurrida
                <input
                  id="manual-occurred-at"
                  type="datetime-local"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 cursor-pointer"
                  value={manualOccurredAt}
                  onChange={(e) => setManualOccurredAt(e.target.value)}
                  required
                />
              </label>

              <label htmlFor="manual-reason" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Motivo / Justificación (Auditable - min 3 caract.)
                <textarea
                  id="manual-reason"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 min-h-[80px]"
                  placeholder="Ej. Alumno se retira temprano por cita médica familiar..."
                  value={manualReason}
                  onChange={(e) => setManualReason(e.target.value)}
                  required
                  minLength={3}
                />
              </label>

              {manualError && (
                <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                  {manualError}
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                  onClick={() => setShowManualModal(false)}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-blue-500/20 transition-all"
                  disabled={createManualEventMutation.isPending}
                >
                  {createManualEventMutation.isPending ? 'Procesando...' : 'Registrar Evento'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 2: Justificar Falta (All superadmin, auxiliar, toe) */}
      {showJustifyModal && selectedRecord && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
              <FileText className="text-blue-600" size={24} />
              Justificar Falta de Asistencia
            </h3>
            <p className="text-sm text-slate-600 leading-relaxed">
              Justifica la falta para el estudiante <strong className="text-slate-900">{selectedRecord.student_name}</strong> en la fecha <span className="font-semibold text-slate-700">{selectedRecord.date}</span>.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                justifyAbsenceMutation.mutate({
                  attendanceId: selectedRecord.id,
                  reason: justifyReason
                })
              }}
              className="space-y-4"
            >
              <label htmlFor="justify-reason" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                Motivo de la Justificación (Auditable - min 3 caract.)
                <textarea
                  id="justify-reason"
                  className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 min-h-[80px]"
                  placeholder="Ej. Presenta certificado médico original de reposo por 24 horas..."
                  value={justifyReason}
                  onChange={(e) => setJustifyReason(e.target.value)}
                  required
                  minLength={3}
                />
              </label>

              {justifyError && (
                <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                  {justifyError}
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                  onClick={() => {
                    setShowJustifyModal(false)
                    setSelectedRecord(null)
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-blue-500/20 transition-all"
                  disabled={justifyAbsenceMutation.isPending}
                >
                  {justifyAbsenceMutation.isPending ? 'Guardando...' : 'Confirmar Justificación'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 3: Resolver Anomalía (Only superadmin & auxiliar) */}
      {showResolveAnomalyModal && selectedAnomaly && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
              <Warning className="text-amber-500" size={24} weight="fill" />
              Resolver Ingreso sin Salida
            </h3>
            <p className="text-sm text-slate-600 leading-relaxed">
              El alumno <strong className="text-slate-900">{selectedAnomaly.student_name}</strong> ingresó a las <span className="font-semibold text-slate-700">{new Date(selectedAnomaly.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span> pero no registró salida. Debes registrar manualmente la salida.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                resolveAnomalyMutation.mutate({
                  anomalyId: selectedAnomaly.id,
                  studentId: selectedAnomaly.student_id,
                  exitTime: new Date(anomalyExitTime).toISOString(),
                  reason: anomalyReason
                })
              }}
              className="space-y-4"
            >
              <label htmlFor="anomaly-exit-time" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                Hora y Fecha Real Ocurrida de la Salida
                <input
                  id="anomaly-exit-time"
                  type="datetime-local"
                  className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 cursor-pointer"
                  value={anomalyExitTime}
                  onChange={(e) => setAnomalyExitTime(e.target.value)}
                  required
                />
              </label>

              <label htmlFor="anomaly-reason" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                Motivo de la Corrección (Auditable - min 3 caract.)
                <textarea
                  id="anomaly-reason"
                  className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 min-h-[80px]"
                  placeholder="Ej. Salió en movilidad escolar autorizada sin pasar por el tótem facial..."
                  value={anomalyReason}
                  onChange={(e) => setAnomalyReason(e.target.value)}
                  required
                  minLength={3}
                />
              </label>

              {anomalyError && (
                <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                  {anomalyError}
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                  onClick={() => {
                    setShowResolveAnomalyModal(false)
                    setSelectedAnomaly(null)
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-blue-500/20 transition-all"
                  disabled={resolveAnomalyMutation.isPending}
                >
                  {resolveAnomalyMutation.isPending ? 'Guardando...' : 'Resolver Anomalía'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 4: Review Recognition Modal (All superadmin & auxiliar) */}
      {showReviewModal && selectedEvent && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-lg font-bold text-slate-950">
              Revisar Reconocimiento Dudoso
            </h3>
            <p className="text-sm text-slate-600">
              Decide cómo clasificar este evento capturado de forma dudosa en la estación.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                reviewRecognitionMutation.mutate({
                  eventId: selectedEvent.id,
                  outcome: reviewOutcome,
                  reason: reviewReason,
                  matchedStudentId: reviewMatchedStudentId || undefined
                })
              }}
              className="space-y-4"
            >
              <label htmlFor="review-outcome" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Decisión
                <select
                  id="review-outcome"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 cursor-pointer"
                  value={reviewOutcome}
                  onChange={(e) => setReviewOutcome(e.target.value as 'confirmed' | 'rejected' | 'reassigned')}
                >
                  <option value="confirmed">Confirmar (Es el alumno identificado)</option>
                  <option value="rejected">Rechazar (No es alumno válido o es inválido)</option>
                  <option value="reassigned">Reasignar a otro alumno</option>
                </select>
              </label>

              {reviewOutcome === 'reassigned' && (
                <label htmlFor="review-student-id" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                  Alumno correcto por DNI o codigo interno
                  <input
                    id="review-student-id"
                    type="text"
                    className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200"
                    placeholder="Ej. 00000000-0000-0000-0000-000000000000"
                    value={reviewMatchedStudentId}
                    onChange={(e) => setReviewMatchedStudentId(e.target.value)}
                    required={reviewOutcome === 'reassigned'}
                  />
                </label>
              )}

              <label htmlFor="review-reason" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1">
                Motivo de la Decisión (Auditable - min 3 caract.)
                <textarea
                  id="review-reason"
                  className="glass-input-light w-full mt-1 p-2.5 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 min-h-[80px]"
                  placeholder="Ej. Rostro coincide plenamente con foto oficial del alumno..."
                  value={reviewReason}
                  onChange={(e) => setReviewReason(e.target.value)}
                  required
                  minLength={3}
                />
              </label>

              {reviewError && (
                <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                  {reviewError}
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                  onClick={() => {
                    setShowReviewModal(false)
                    setSelectedEvent(null)
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-blue-500/20 transition-all"
                  disabled={reviewRecognitionMutation.isPending}
                >
                  {reviewRecognitionMutation.isPending ? 'Guardando...' : 'Confirmar Decisión'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 5: Anular Asistencia Modal */}
      {showDeleteModal && selectedRecord && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
              <X className="text-rose-500" size={24} weight="bold" />
              Anular Asistencia
            </h3>
            <p className="text-sm text-slate-600 leading-relaxed">
              Estás a punto de anular la asistencia de <strong className="text-slate-900">{selectedRecord.student_name}</strong>. Esta acción quedará registrada.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                deleteAttendanceMutation.mutate({
                  attendanceId: selectedRecord.id,
                  reason: deleteReason
                })
              }}
              className="space-y-4"
            >
              <label htmlFor="delete-reason" className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                Motivo de Anulación (Auditable - min 5 caract.)
                <textarea
                  id="delete-reason"
                  className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal border border-slate-200 min-h-[80px]"
                  placeholder="Ej. Registro duplicado, error de marcación..."
                  value={deleteReason}
                  onChange={(e) => setDeleteReason(e.target.value)}
                  required
                  minLength={5}
                />
              </label>

              {deleteError && (
                <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                  {deleteError}
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                  onClick={() => {
                    setShowDeleteModal(false)
                    setSelectedRecord(null)
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-rose-500/20 transition-all"
                  disabled={deleteAttendanceMutation.isPending}
                >
                  {deleteAttendanceMutation.isPending ? 'Anulando...' : 'Anular Asistencia'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </section>
  )
}
