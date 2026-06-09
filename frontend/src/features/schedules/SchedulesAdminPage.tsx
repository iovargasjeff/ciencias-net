import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Calendar,
  Plus,
  Warning,
  SpinnerGap,
  Clock,
  MapPin,
  User,
  CalendarBlank
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { listAcademic, listAccounts } from '@/features/phase-one/api'
import { getApiError } from '@/lib/api/client'
import {
  listSchedules,
  createSchedule,
  listCalendarEvents,
  createCalendarEvent
} from './api'
import type { CreateScheduleInput, CreateCalendarEventInput } from './types'

export function SchedulesAdminPage() {
  const { user } = useAuth()
  const queryClient = useQueryClient()

  // Roles check
  const isDocente = user?.roles.includes('docente') && !user?.roles.some((r) => ['superadmin', 'coordinador_academico'].includes(r))
  const canEdit = user?.roles.some((r) => ['superadmin', 'coordinador_academico'].includes(r))

  // Tabs: 'schedules' | 'events'
  const [activeTab, setActiveTab] = useState<'schedules' | 'events'>('schedules')

  // Selection states (for coordinators)
  const [viewMode, setViewMode] = useState<'section' | 'teacher'>('section')
  const [selectedSectionId, setSelectedSectionId] = useState('')
  const [selectedTeacherId, setSelectedTeacherId] = useState('')

  // Modal control states
  const [showScheduleModal, setShowScheduleModal] = useState(false)
  const [showEventModal, setShowEventModal] = useState(false)

  // Create schedule form state
  const [formAssignmentId, setFormAssignmentId] = useState('')
  const [formWeekday, setFormWeekday] = useState('1') // "1" to "7"
  const [formStartsAt, setFormStartsAt] = useState('08:00')
  const [formEndsAt, setFormEndsAt] = useState('09:30')
  const [formRoom, setFormRoom] = useState('')
  const [scheduleError, setScheduleError] = useState('')

  // Create event form state
  const [formEventTitle, setFormEventTitle] = useState('')
  const [formEventStartsAt, setFormEventStartsAt] = useState('')
  const [formEventEndsAt, setFormEventEndsAt] = useState('')
  const [formEventType, setFormEventType] = useState<'academic' | 'holiday' | 'meeting' | 'other'>('academic')
  const [formEventDesc, setFormEventDesc] = useState('')
  const [eventError, setEventError] = useState('')

  // 1. Query Academic Metadata
  const assignmentsQuery = useQuery({
    queryKey: ['academic', 'teaching-assignments'],
    queryFn: () => listAcademic('teaching-assignments')
  })

  const coursesQuery = useQuery({
    queryKey: ['academic', 'courses'],
    queryFn: () => listAcademic('courses')
  })

  const sectionsQuery = useQuery({
    queryKey: ['academic', 'sections'],
    queryFn: () => listAcademic('sections')
  })

  const gradesQuery = useQuery({
    queryKey: ['academic', 'grades'],
    queryFn: () => listAcademic('grades')
  })

  const accountsQuery = useQuery({
    queryKey: ['accounts'],
    queryFn: () => listAccounts()
  })

  const rawAssignments = assignmentsQuery.data?.data || []
  const sections = sectionsQuery.data?.data || []
  const courses = coursesQuery.data?.data || []
  const grades = gradesQuery.data?.data || []
  const accounts = accountsQuery.data?.data || []

  // Helper mappings
  const getCourseName = (courseId?: string) => {
    const course = courses.find((c) => c.id === courseId)
    return course?.name || `Curso (${courseId?.slice(0, 8)})`
  }

  const getSectionName = (sectionId?: string) => {
    const sec = sections.find((s) => s.id === sectionId)
    const grade = grades.find((g) => g.id === sec?.grade_id)
    return sec ? `${grade?.name || ''} "${sec.name}"` : `Sección (${sectionId?.slice(0, 8)})`
  }

  const getTeacherName = (teacherId?: string) => {
    const t = accounts.find((a) => a.id === teacherId)
    return t?.name || `Docente (${teacherId?.slice(0, 8)})`
  }

  // 2. Fetch Schedules based on query filters
  // If teacher: filter by teacher_id = user.id
  // If coord viewMode === 'section': filter by section_id
  // If coord viewMode === 'teacher': filter by teacher_id
  const scheduleParams = isDocente
    ? { teacher_id: user?.id }
    : viewMode === 'section'
    ? { section_id: selectedSectionId || 'none' }
    : { teacher_id: selectedTeacherId || 'none' }

  const schedulesQuery = useQuery({
    queryKey: ['schedules', scheduleParams],
    queryFn: () => listSchedules(scheduleParams),
    enabled: isDocente || (viewMode === 'section' ? !!selectedSectionId : !!selectedTeacherId)
  })

  const schedules = schedulesQuery.data?.data || []

  // 3. Fetch Calendar Events
  const eventsQuery = useQuery({
    queryKey: ['calendar-events'],
    queryFn: () => listCalendarEvents()
  })

  const events = eventsQuery.data?.data || []

  // List of teachers for coordinator selector
  const teachersList = accounts.filter(acc => acc.roles.includes('docente'))

  // Group schedules by weekday (1 = Lunes, ..., 7 = Domingo)
  const weekdays = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' }
  ]

  const getSchedulesForDay = (dayValue: number) => {
    return schedules
      .filter((s) => s.weekday === dayValue)
      .sort((a, b) => a.starts_at.localeCompare(b.starts_at))
  }

  // Find teaching assignment details
  const getAssignmentDetails = (assignmentId: string) => {
    const assignment = rawAssignments.find((a) => a.id === assignmentId)
    return {
      course: getCourseName(assignment?.course_id),
      section: getSectionName(assignment?.section_id),
      teacher: getTeacherName(assignment?.teacher_id)
    }
  }

  // --- MUTATIONS ---

  const createScheduleMutation = useMutation({
    mutationFn: async (input: CreateScheduleInput) => createSchedule(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['schedules'] })
      setShowScheduleModal(false)
      resetScheduleForm()
    },
    onError: (err: unknown) => {
      setScheduleError(getApiError(err).message)
    }
  })

  const createEventMutation = useMutation({
    mutationFn: async (input: CreateCalendarEventInput) => createCalendarEvent(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendar-events'] })
      setShowEventModal(false)
      resetEventForm()
    },
    onError: (err: unknown) => {
      setEventError(getApiError(err).message)
    }
  })

  // --- SUBMITS ---

  const handleScheduleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setScheduleError('')

    if (!formAssignmentId) {
      setScheduleError('Debe seleccionar una carga académica (Curso/Docente).')
      return
    }

    if (!formStartsAt || !formEndsAt) {
      setScheduleError('Las horas de inicio y fin son obligatorias.')
      return
    }

    if (formStartsAt >= formEndsAt) {
      setScheduleError('La hora de fin debe ser posterior a la hora de inicio.')
      return
    }

    createScheduleMutation.mutate({
      teaching_assignment_id: formAssignmentId,
      weekday: Number(formWeekday),
      starts_at: formStartsAt,
      ends_at: formEndsAt,
      room: formRoom || undefined
    })
  }

  const handleEventSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setEventError('')

    if (!formEventTitle.trim()) {
      setEventError('El título de la actividad es obligatorio.')
      return
    }

    if (!formEventStartsAt || !formEventEndsAt) {
      setEventError('Las fechas y horas de inicio y fin son obligatorias.')
      return
    }

    if (formEventStartsAt >= formEventEndsAt) {
      setEventError('La fecha de fin debe ser posterior a la fecha de inicio.')
      return
    }

    createEventMutation.mutate({
      title: formEventTitle,
      starts_at: new Date(formEventStartsAt).toISOString(),
      ends_at: new Date(formEventEndsAt).toISOString(),
      event_type: formEventType,
      description: formEventDesc || undefined
    })
  }

  const resetScheduleForm = () => {
    setFormAssignmentId('')
    setFormWeekday('1')
    setFormStartsAt('08:00')
    setFormEndsAt('09:30')
    setFormRoom('')
    setScheduleError('')
  }

  const resetEventForm = () => {
    setFormEventTitle('')
    setFormEventStartsAt('')
    setFormEventEndsAt('')
    setFormEventType('academic')
    setFormEventDesc('')
    setEventError('')
  }

  // Get assignments of the selected section for schedule form selection
  const selectedSectionAssignments = rawAssignments.filter(
    (a) => a.section_id === selectedSectionId
  )

  const formatDateTime = (isoString: string) => {
    return new Date(isoString).toLocaleString('es-PE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Top Header & Selection Panel */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Calendar className="text-blue-600" size={24} weight="duotone" />
            Horarios y Calendario
          </h2>
          <p className="text-xs text-slate-500">
            {canEdit
              ? 'Administre la programación de clases de las secciones y los eventos escolares.'
              : 'Revise su horario semanal de dictado de clases asignado.'}
          </p>
        </div>

        {canEdit && activeTab === 'schedules' && (
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div className="flex bg-slate-100 p-1 rounded-xl">
              <button
                onClick={() => {
                  setViewMode('section')
                  setSelectedTeacherId('')
                }}
                className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all ${
                  viewMode === 'section' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'
                }`}
              >
                Por Sección
              </button>
              <button
                onClick={() => {
                  setViewMode('teacher')
                  setSelectedSectionId('')
                }}
                className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all ${
                  viewMode === 'teacher' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'
                }`}
              >
                Por Docente
              </button>
            </div>

            {viewMode === 'section' ? (
              <select
                id="section-filter-select"
                className="p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all min-w-[180px]"
                value={selectedSectionId}
                onChange={(e) => setSelectedSectionId(e.target.value)}
              >
                <option value="">-- Seleccionar Sección --</option>
                {sections.map((sec) => (
                  <option key={sec.id} value={sec.id}>
                    {getSectionName(sec.id)}
                  </option>
                ))}
              </select>
            ) : (
              <select
                id="teacher-filter-select"
                className="p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all min-w-[180px]"
                value={selectedTeacherId}
                onChange={(e) => setSelectedTeacherId(e.target.value)}
              >
                <option value="">-- Seleccionar Docente --</option>
                {teachersList.map((teach) => (
                  <option key={teach.id} value={teach.id}>
                    {teach.name}
                  </option>
                ))}
              </select>
            )}
          </div>
        )}
      </div>

      {/* Tabs Menu */}
      <div className="flex border-b border-slate-200 gap-6">
        <button
          onClick={() => setActiveTab('schedules')}
          className={`pb-3 text-sm font-bold transition-all border-b-2 ${
            activeTab === 'schedules' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-650'
          }`}
        >
          Horarios de Clase
        </button>
        <button
          onClick={() => setActiveTab('events')}
          className={`pb-3 text-sm font-bold transition-all border-b-2 ${
            activeTab === 'events' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-650'
          }`}
        >
          Calendario de Actividades
        </button>
      </div>

      {/* Active Tab: Schedules */}
      {activeTab === 'schedules' && (
        <div className="space-y-4">
          {canEdit && viewMode === 'section' && selectedSectionId && (
            <div className="flex justify-end">
              <button
                onClick={() => {
                  setScheduleError('')
                  setShowScheduleModal(true)
                }}
                className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-3.5 py-2 font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/10 hover:shadow-lg transition-all"
              >
                <Plus size={16} weight="bold" /> Programar Clase
              </button>
            </div>
          )}

          {/* Grid Layout of Class Schedules */}
          {(!isDocente && viewMode === 'section' && !selectedSectionId) ||
          (!isDocente && viewMode === 'teacher' && !selectedTeacherId) ? (
            <div className="flex flex-col items-center justify-center p-16 text-center bg-white/40 border border-dashed border-slate-200 rounded-3xl min-h-[300px]">
              <div className="p-4 bg-blue-50 rounded-full text-blue-600 mb-4 animate-pulse">
                <CalendarBlank size={36} weight="duotone" />
              </div>
              <h3 className="text-base font-bold text-slate-800">Seleccione un Filtro</h3>
              <p className="text-xs text-slate-500 max-w-sm mt-1 leading-relaxed">
                Por favor, elija una sección o docente en el selector superior para cargar el horario correspondiente.
              </p>
            </div>
          ) : schedulesQuery.isLoading ? (
            <div className="flex flex-col items-center justify-center py-12">
              <SpinnerGap className="spin text-blue-500" size={32} />
              <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando horario...</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
              {weekdays.map((day) => {
                const daySchedules = getSchedulesForDay(day.value)
                return (
                  <div
                    key={day.value}
                    className="bg-white/70 border border-slate-100 rounded-2xl p-4 shadow-sm flex flex-col space-y-3"
                  >
                    <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-2 uppercase tracking-wider text-center bg-slate-50 py-1 rounded-lg">
                      {day.label}
                    </h3>
                    <div className="flex-1 space-y-2.5">
                      {daySchedules.length === 0 ? (
                        <p className="text-[10px] text-slate-400 italic text-center py-6">
                          Sin clases programadas
                        </p>
                      ) : (
                        daySchedules.map((schedule) => {
                          const details = getAssignmentDetails(schedule.teaching_assignment_id)
                          return (
                            <div
                              key={schedule.id}
                              className="p-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-100 rounded-xl space-y-2 transition-all"
                            >
                              <div className="flex items-center justify-between gap-1 text-[10px] font-bold text-blue-600">
                                <span className="flex items-center gap-0.5">
                                  <Clock size={11} />
                                  {schedule.starts_at} - {schedule.ends_at}
                                </span>
                              </div>
                              <div className="space-y-0.5">
                                <h4 className="text-xs font-extrabold text-slate-800 line-clamp-2 leading-tight">
                                  {details.course}
                                </h4>
                                <p className="text-[10px] text-slate-500 font-medium flex items-center gap-1">
                                  <User size={10} className="text-slate-450" />
                                  {viewMode === 'teacher' || isDocente ? details.section : details.teacher}
                                </p>
                                {schedule.room && (
                                  <p className="text-[9px] text-slate-400 font-bold flex items-center gap-1 bg-slate-100/60 w-fit px-1.5 py-0.5 rounded-md">
                                    <MapPin size={9} />
                                    Aula: {schedule.room}
                                  </p>
                                )}
                              </div>
                            </div>
                          )
                        })
                      )}
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </div>
      )}

      {/* Active Tab: Calendar Events */}
      {activeTab === 'events' && (
        <div className="space-y-4 max-w-4xl mx-auto">
          {canEdit && (
            <div className="flex justify-end">
              <button
                onClick={() => {
                  setEventError('')
                  setShowEventModal(true)
                }}
                className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-3.5 py-2 font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/10 hover:shadow-lg transition-all"
              >
                <Plus size={16} weight="bold" /> Crear Actividad / Feriado
              </button>
            </div>
          )}

          {eventsQuery.isLoading ? (
            <div className="flex flex-col items-center justify-center py-12">
              <SpinnerGap className="spin text-blue-500" size={32} />
              <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando actividades...</p>
            </div>
          ) : events.length === 0 ? (
            <div className="flex flex-col items-center justify-center p-12 text-center bg-white border border-slate-100 rounded-3xl">
              <p className="text-xs font-bold text-slate-500">No hay eventos ni feriados registrados.</p>
              <p className="text-[11px] text-slate-450 mt-1">
                {canEdit
                  ? 'Haga clic en "Crear Actividad / Feriado" para registrar el primer evento.'
                  : 'Consulte con coordinación académica.'}
              </p>
            </div>
          ) : (
            <div className="bg-white border border-slate-100 rounded-3xl overflow-hidden shadow-sm">
              <div className="p-5 border-b border-slate-50 bg-slate-50/50">
                <h3 className="text-sm font-bold text-slate-900">Lista Cronológica de Actividades</h3>
              </div>
              <div className="divide-y divide-slate-100">
                {events.map((event) => {
                  const isHoliday = event.event_type === 'holiday'
                  return (
                    <div key={event.id} className="p-5 flex flex-col md:flex-row md:items-start justify-between gap-4 hover:bg-slate-50/40 transition-colors">
                      <div className="space-y-1">
                        <div className="flex items-center gap-2">
                          <h4 className="text-sm font-extrabold text-slate-900">{event.title}</h4>
                          <span
                            className={`px-2 py-0.5 rounded-md text-[9px] font-extrabold tracking-wider uppercase ${
                              isHoliday
                                ? 'bg-red-50 text-red-700'
                                : event.event_type === 'academic'
                                ? 'bg-blue-50 text-blue-700'
                                : event.event_type === 'meeting'
                                ? 'bg-orange-50 text-orange-700'
                                : 'bg-slate-100 text-slate-700'
                            }`}
                          >
                            {event.event_type}
                          </span>
                        </div>
                        {event.description && (
                          <p className="text-xs text-slate-550 leading-relaxed max-w-2xl">{event.description}</p>
                        )}
                      </div>
                      <div className="text-[10px] text-slate-450 font-bold bg-slate-50 border border-slate-100/50 px-2.5 py-1.5 rounded-xl flex flex-col items-end gap-0.5">
                        <span className="flex items-center gap-1 font-mono">
                          <Clock size={11} /> INI: {formatDateTime(event.starts_at)}
                        </span>
                        <span className="flex items-center gap-1 font-mono">
                          <Clock size={11} /> FIN: {formatDateTime(event.ends_at)}
                        </span>
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          )}
        </div>
      )}

      {/* --- CREATE SCHEDULE MODAL --- */}
      {showScheduleModal && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-slate-50 pb-2">
              <CalendarBlank className="text-blue-600" size={24} weight="bold" />
              Programar Nueva Clase
            </h3>

            <form onSubmit={handleScheduleSubmit} className="space-y-4">
              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Curso / Docente *</label>
                <select
                  id="form-assignment-select"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={formAssignmentId}
                  onChange={(e) => setFormAssignmentId(e.target.value)}
                >
                  <option value="">-- Seleccionar Carga --</option>
                  {selectedSectionAssignments.map((a) => (
                    <option key={a.id} value={a.id}>
                      {getCourseName(a.course_id)} ({getTeacherName(a.teacher_id)})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Día *</label>
                  <select
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={formWeekday}
                    onChange={(e) => setFormWeekday(e.target.value)}
                  >
                    {weekdays.map((w) => (
                      <option key={w.value} value={w.value}>
                        {w.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Aula (Opcional)</label>
                  <input
                    type="text"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ej. Salón 102"
                    value={formRoom}
                    onChange={(e) => setFormRoom(e.target.value)}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Hora Inicio *</label>
                  <input
                    type="time"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={formStartsAt}
                    onChange={(e) => setFormStartsAt(e.target.value)}
                  />
                </div>
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Hora Fin *</label>
                  <input
                    type="time"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={formEndsAt}
                    onChange={(e) => setFormEndsAt(e.target.value)}
                  />
                </div>
              </div>

              {scheduleError && (
                <p className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-semibold flex items-start gap-1.5">
                  <Warning size={14} className="mt-0.5 flex-shrink-0" />
                  <span>{scheduleError}</span>
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                  disabled={createScheduleMutation.isPending}
                  onClick={() => {
                    setShowScheduleModal(false)
                    resetScheduleForm()
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50 flex items-center gap-1.5"
                  disabled={createScheduleMutation.isPending}
                >
                  {createScheduleMutation.isPending ? (
                    <>
                      <SpinnerGap className="spin" size={14} /> Guardando...
                    </>
                  ) : (
                    'Guardar'
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- CREATE CALENDAR EVENT MODAL --- */}
      {showEventModal && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-slate-50 pb-2">
              <Calendar className="text-blue-600" size={24} weight="bold" />
              Crear Actividad / Feriado
            </h3>

            <form onSubmit={handleEventSubmit} className="space-y-4">
              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Título *</label>
                <input
                  type="text"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ej. Feriado del Día del Maestro"
                  value={formEventTitle}
                  onChange={(e) => setFormEventTitle(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Tipo de Evento *</label>
                <select
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={formEventType}
                  onChange={(e) => setFormEventType(e.target.value as 'academic' | 'holiday' | 'meeting' | 'other')}
                >
                  <option value="academic">Académico</option>
                  <option value="holiday">Día No Laboral / Feriado</option>
                  <option value="meeting">Reunión de Padres</option>
                  <option value="other">Otro</option>
                </select>
              </div>

              <div className="grid grid-cols-1 gap-4">
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Fecha/Hora Inicio *</label>
                  <input
                    type="datetime-local"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={formEventStartsAt}
                    onChange={(e) => setFormEventStartsAt(e.target.value)}
                  />
                </div>
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Fecha/Hora Fin *</label>
                  <input
                    type="datetime-local"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={formEventEndsAt}
                    onChange={(e) => setFormEventEndsAt(e.target.value)}
                  />
                </div>
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Descripción (Opcional)</label>
                <textarea
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[70px]"
                  placeholder="Detalles sobre el evento..."
                  value={formEventDesc}
                  onChange={(e) => setFormEventDesc(e.target.value)}
                />
              </div>

              {eventError && (
                <p className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-semibold flex items-start gap-1.5">
                  <Warning size={14} className="mt-0.5 flex-shrink-0" />
                  <span>{eventError}</span>
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                  disabled={createEventMutation.isPending}
                  onClick={() => {
                    setShowEventModal(false)
                    resetEventForm()
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50 flex items-center gap-1.5"
                  disabled={createEventMutation.isPending}
                >
                  {createEventMutation.isPending ? (
                    <>
                      <SpinnerGap className="spin" size={14} /> Guardando...
                    </>
                  ) : (
                    'Guardar'
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </section>
  )
}
