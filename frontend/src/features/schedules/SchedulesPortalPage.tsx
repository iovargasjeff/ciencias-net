import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  Calendar,
  Clock,
  MapPin,
  User,
  Warning,
  SpinnerGap,
  CaretLeft,
  CaretRight,
  BookOpen,
  CalendarCheck
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { getStudentSummary, listLinkedStudents, listAcademic } from '@/features/phase-one/api'
import { listSchedules, listCalendarEvents } from './api'
import type { CalendarEvent } from './types'

const contextKey = 'cienciasnet:selected-student-id'

export function SchedulesPortalPage() {
  const { user } = useAuth()
  const isParent = user?.roles.includes('padre') === true
  const isStudent = user?.roles.includes('alumno') === true

  // Linked students (only for parents)
  const studentsQuery = useQuery({
    queryKey: ['linked-students'],
    queryFn: listLinkedStudents,
    enabled: isParent
  })

  // Selected student
  const [selected, setSelected] = useState(() => sessionStorage.getItem(contextKey) ?? '')
  const selectedId = isStudent ? (user?.id || '') : (selected || studentsQuery.data?.[0]?.id || '')

  const handleStudentChange = (id: string) => {
    setSelected(id)
    sessionStorage.setItem(contextKey, id)
  }

  // 1. Fetch student summary details
  const summaryQuery = useQuery({
    queryKey: ['student-summary', selectedId],
    queryFn: () => getStudentSummary(selectedId),
    enabled: Boolean(selectedId)
  })

  // 2. Query Academic structures to map section info
  const sectionsQuery = useQuery({
    queryKey: ['academic', 'sections'],
    queryFn: () => listAcademic('sections'),
    enabled: !!selectedId
  })

  const gradesQuery = useQuery({
    queryKey: ['academic', 'grades'],
    queryFn: () => listAcademic('grades'),
    enabled: !!selectedId
  })

  const coursesQuery = useQuery({
    queryKey: ['academic', 'courses'],
    queryFn: () => listAcademic('courses'),
    enabled: !!selectedId
  })

  const assignmentsQuery = useQuery({
    queryKey: ['academic', 'assignments'],
    queryFn: () => listAcademic('teaching-assignments'),
    enabled: !!selectedId
  })

  // 3. Resolve active enrollment section for Aislamiento por Matrícula
  const availableSections = sectionsQuery.data?.data || []
  const availableGrades = gradesQuery.data?.data || []
  const availableCourses = coursesQuery.data?.data || []
  const availableAssignments = assignmentsQuery.data?.data || []

  const getGradeName = (gradeId?: string) => {
    return availableGrades.find((g) => g.id === gradeId)?.name || ''
  }

  const enrolledSections = availableSections.filter((sec) => {
    return summaryQuery.data?.enrollments.some(
      (e) => e.section === sec.name && e.grade === getGradeName(sec.grade_id)
    )
  })

  const activeSectionId = enrolledSections[0]?.id || ''
  const studentAssignmentIds = availableAssignments
    .filter((a) => a.section_id === activeSectionId)
    .map((a) => a.id)

  // 4. Fetch schedules matching the active student's section
  const { data: schedulesData, isLoading: isLoadingSchedules } = useQuery({
    queryKey: ['portal-schedules', activeSectionId],
    queryFn: () => listSchedules({ section_id: activeSectionId }),
    enabled: !!activeSectionId
  })

  const rawSchedules = schedulesData?.data || []
  // Filter in frontend to guarantee absolute Aislamiento por Matrícula
  const schedules = rawSchedules.filter((s) => studentAssignmentIds.includes(s.teaching_assignment_id))

  // 5. Fetch calendar events
  const { data: eventsData, isLoading: isLoadingEvents } = useQuery({
    queryKey: ['portal-calendar-events'],
    queryFn: () => listCalendarEvents()
  })

  const events = eventsData?.data || []

  // Helpers to resolve metadata names
  const getCourseNameForAssignment = (assignmentId: string) => {
    const assign = availableAssignments.find((a) => a.id === assignmentId)
    const course = availableCourses.find((c) => c.id === assign?.course_id)
    return course?.name || 'Curso'
  }

  // Timetable helpers
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

  // --- CALENDAR MANAGEMENT STATE & HELPERS ---
  const [currentDate, setCurrentDate] = useState(() => new Date())
  const [selectedDate, setSelectedDate] = useState<Date | null>(() => new Date())
  const [activeTab, setActiveTab] = useState<'schedule' | 'calendar'>('schedule')

  const handlePrevMonth = () => {
    setCurrentDate((prev) => new Date(prev.getFullYear(), prev.getMonth() - 1, 1))
  }

  const handleNextMonth = () => {
    setCurrentDate((prev) => new Date(prev.getFullYear(), prev.getMonth() + 1, 1))
  }

  const year = currentDate.getFullYear()
  const month = currentDate.getMonth()

  // First day weekday index (0 = Sun, 1 = Mon, ..., 6 = Sat)
  const firstDayIndex = new Date(year, month, 1).getDay()
  const numDays = new Date(year, month + 1, 0).getDate()

  const calendarDays: (Date | null)[] = []
  // Pad beginning of the calendar grid
  for (let i = 0; i < firstDayIndex; i++) {
    calendarDays.push(null)
  }
  // Fill calendar days
  for (let i = 1; i <= numDays; i++) {
    calendarDays.push(new Date(year, month, i))
  }

  const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]

  // Verify if a day is in event date range
  const isDateInEventRange = (d: Date, event: CalendarEvent) => {
    const start = new Date(event.starts_at)
    start.setHours(0, 0, 0, 0)
    const end = new Date(event.ends_at)
    end.setHours(23, 59, 59, 999)
    return d.getTime() >= start.getTime() && d.getTime() <= end.getTime()
  }

  const getEventsForDay = (d: Date | null) => {
    if (!d) return []
    return events.filter((e) => isDateInEventRange(d, e))
  }

  const formatTimeRange = (startsAtStr: string, endsAtStr: string) => {
    const starts = new Date(startsAtStr).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', hour12: false })
    const ends = new Date(endsAtStr).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', hour12: false })
    return `${starts} - ${ends}`
  }

  if (!isParent && !isStudent) {
    return (
      <div className="flex flex-col items-center justify-center p-12 text-center bg-red-50 border border-red-100 rounded-3xl text-red-800">
        <Warning size={36} className="text-red-500" />
        <h3 className="text-base font-bold mt-2">Acceso Denegado</h3>
        <p className="text-xs text-red-655 mt-1">Su cuenta no tiene permisos para acceder al portal familiar o de alumnos.</p>
      </div>
    )
  }

  const isLoading = summaryQuery.isLoading || isLoadingSchedules || isLoadingEvents

  const activeStudentName = summaryQuery.data?.name || 'Alumno'
  const activeStudentSection = enrolledSections[0]
    ? `${getGradeName(enrolledSections[0].grade_id)} "${enrolledSections[0].name}"`
    : ''

  const dayEvents = selectedDate ? getEventsForDay(selectedDate) : []

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Selector and Header Panel */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Calendar className="text-blue-600" size={24} weight="duotone" />
            Horario y Calendario
          </h2>
          <p className="text-xs text-slate-500">
            Consulte su horario escolar semanal y manténgase informado de feriados y actividades.
          </p>
        </div>

        {isParent && (studentsQuery.data?.length || 0) > 1 && (
          <div className="w-full md:w-64">
            <label htmlFor="student-select" className="text-[10px] font-extrabold text-slate-400 block mb-1 uppercase tracking-wider">Hijo / Alumno</label>
            <div className="relative">
              <select
                id="student-select"
                className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-850 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none pr-8 pr-8"
                value={selectedId}
                onChange={(e) => handleStudentChange(e.target.value)}
              >
                {studentsQuery.data?.map((student) => (
                  <option key={student.id} value={student.id}>
                    {student.name} ({student.relationship})
                  </option>
                ))}
              </select>
              <User size={14} className="absolute right-3 top-3.5 text-slate-400 pointer-events-none" />
            </div>
          </div>
        )}
      </div>

      {/* Tabs Selector */}
      <div className="flex border-b border-slate-200 gap-6">
        <button
          onClick={() => setActiveTab('schedule')}
          className={`pb-3 text-sm font-bold transition-all border-b-2 ${
            activeTab === 'schedule' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-650'
          }`}
        >
          Horario de Clases
        </button>
        <button
          onClick={() => setActiveTab('calendar')}
          className={`pb-3 text-sm font-bold transition-all border-b-2 ${
            activeTab === 'calendar' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-650'
          }`}
        >
          Calendario Escolar
        </button>
      </div>

      {isLoading ? (
        <div className="flex flex-col items-center justify-center py-16">
          <SpinnerGap className="spin text-blue-500" size={36} />
          <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando horario y eventos...</p>
        </div>
      ) : (
        <div className="space-y-4">
          {/* Tab 1: Horario de Clases */}
          {activeTab === 'schedule' && (
            <div className="space-y-4">
              <div className="bg-slate-50 border border-slate-100 p-4 rounded-2xl flex items-center justify-between">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">{activeStudentName}</h3>
                  <p className="text-xs text-slate-500 font-medium">Sección de Matrícula: {activeStudentSection || 'No matriculado'}</p>
                </div>
                <BookOpen size={24} className="text-blue-500" />
              </div>

              {!activeSectionId ? (
                <div className="flex flex-col items-center justify-center p-16 text-center bg-white border border-slate-100 rounded-3xl">
                  <Warning size={32} className="text-amber-500 mb-2" />
                  <p className="text-xs font-bold text-slate-500">Este alumno no cuenta con una matrícula activa para este periodo.</p>
                  <p className="text-[11px] text-slate-400 mt-1">Por favor consulte con la coordinación académica.</p>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                  {weekdays.map((day) => {
                    const daySchedules = getSchedulesForDay(day.value)
                    return (
                      <div key={day.value} className="bg-white border border-slate-100/90 rounded-2xl p-4 shadow-sm flex flex-col space-y-3">
                        <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-2 uppercase tracking-wider text-center bg-slate-50 py-1 rounded-lg">
                          {day.label}
                        </h3>
                        <div className="space-y-2.5 flex-1">
                          {daySchedules.length === 0 ? (
                            <p className="text-[10px] text-slate-400 italic text-center py-6">Sin clases</p>
                          ) : (
                            daySchedules.map((schedule) => {
                              const courseName = getCourseNameForAssignment(schedule.teaching_assignment_id)
                              return (
                                <div key={schedule.id} className="p-3 bg-slate-50/50 hover:bg-slate-50 border border-slate-100 rounded-xl space-y-2 transition-all">
                                  <div className="flex items-center justify-between text-[10px] font-bold text-blue-600">
                                    <span className="flex items-center gap-0.5">
                                      <Clock size={11} />
                                      {schedule.starts_at} - {schedule.ends_at}
                                    </span>
                                  </div>
                                  <div className="space-y-0.5">
                                    <h4 className="text-xs font-extrabold text-slate-800 line-clamp-2 leading-tight">
                                      {courseName}
                                    </h4>
                                    {schedule.room && (
                                      <p className="text-[9px] text-slate-400 font-bold flex items-center gap-1 bg-slate-100/60 w-fit px-1.5 py-0.5 rounded-md mt-1">
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

          {/* Tab 2: Calendario Escolar */}
          {activeTab === 'calendar' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
              {/* Calendar Grid Container */}
              <div className="lg:col-span-2 bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
                {/* Month selectors */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-50">
                  <h3 className="text-sm font-extrabold text-slate-900 tracking-tight">
                    {monthNames[month]} {year}
                  </h3>
                  <div className="flex gap-1.5">
                    <button
                      onClick={handlePrevMonth}
                      className="p-1.5 hover:bg-slate-100 rounded-lg text-slate-600 transition-colors"
                      title="Mes anterior"
                    >
                      <CaretLeft size={16} weight="bold" />
                    </button>
                    <button
                      onClick={handleNextMonth}
                      className="p-1.5 hover:bg-slate-100 rounded-lg text-slate-600 transition-colors"
                      title="Mes siguiente"
                    >
                      <CaretRight size={16} weight="bold" />
                    </button>
                  </div>
                </div>

                {/* Grid Headings */}
                <div className="grid grid-cols-7 gap-2 text-center text-[10px] font-black text-slate-450 uppercase tracking-wider">
                  <div>Dom</div>
                  <div>Lun</div>
                  <div>Mar</div>
                  <div>Mié</div>
                  <div>Jue</div>
                  <div>Vie</div>
                  <div>Sáb</div>
                </div>

                {/* Grid Days */}
                <div className="grid grid-cols-7 gap-2 text-xs font-bold">
                  {calendarDays.map((day, idx) => {
                    if (!day) return <div key={`empty-${idx}`} className="h-10 md:h-12 bg-slate-50/30 rounded-xl" />

                    const dayEvents = getEventsForDay(day)
                    const isSelected = selectedDate &&
                      selectedDate.getFullYear() === day.getFullYear() &&
                      selectedDate.getMonth() === day.getMonth() &&
                      selectedDate.getDate() === day.getDate()

                    const hasHoliday = dayEvents.some((e) => e.event_type === 'holiday')
                    const hasAcademic = dayEvents.some((e) => e.event_type === 'academic')
                    const hasMeeting = dayEvents.some((e) => e.event_type === 'meeting')

                    return (
                      <button
                        key={day.toISOString()}
                        onClick={() => setSelectedDate(day)}
                        className={`h-10 md:h-12 rounded-xl flex flex-col items-center justify-between p-1.5 transition-all relative border ${
                          isSelected
                            ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-500/20'
                            : hasHoliday
                            ? 'bg-red-50/50 border-red-100 text-red-700 hover:bg-red-50'
                            : 'bg-white border-slate-100 text-slate-800 hover:bg-slate-50'
                        }`}
                      >
                        <span className="text-[10px]">{day.getDate()}</span>
                        {/* Event Indicator Dots */}
                        <div className="flex justify-center gap-0.5 mt-0.5">
                          {hasHoliday && (
                            <span className={`w-1 h-1 rounded-full ${isSelected ? 'bg-white' : 'bg-red-500'}`} />
                          )}
                          {hasAcademic && (
                            <span className={`w-1 h-1 rounded-full ${isSelected ? 'bg-white' : 'bg-blue-500'}`} />
                          )}
                          {hasMeeting && (
                            <span className={`w-1 h-1 rounded-full ${isSelected ? 'bg-white' : 'bg-orange-500'}`} />
                          )}
                          {!hasHoliday && !hasAcademic && !hasMeeting && dayEvents.length > 0 && (
                            <span className={`w-1 h-1 rounded-full ${isSelected ? 'bg-white' : 'bg-slate-400'}`} />
                          )}
                        </div>
                      </button>
                    )
                  })}
                </div>
              </div>

              {/* Day Details panel */}
              <div className="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4 flex flex-col">
                <h3 className="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
                  <CalendarCheck size={16} weight="bold" className="text-blue-500" />
                  Actividades del Día
                </h3>

                {selectedDate ? (
                  <div className="flex-1 space-y-3 overflow-y-auto">
                    <p className="text-[10px] text-slate-450 font-extrabold">
                      {selectedDate.toLocaleDateString('es-PE', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                      })}
                    </p>

                    {dayEvents.length === 0 ? (
                      <div className="flex flex-col items-center justify-center py-12 text-center">
                        <p className="text-xs text-slate-400 italic">No hay actividades programadas</p>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {dayEvents.map((event) => (
                          <div key={event.id} className="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1.5">
                            <div className="flex items-center gap-1.5">
                              <span
                                className={`w-2 h-2 rounded-full ${
                                  event.event_type === 'holiday'
                                    ? 'bg-red-500'
                                    : event.event_type === 'academic'
                                    ? 'bg-blue-500'
                                    : event.event_type === 'meeting'
                                    ? 'bg-orange-500'
                                    : 'bg-slate-400'
                                }`}
                              />
                              <h4 className="text-xs font-extrabold text-slate-900">{event.title}</h4>
                            </div>
                            {event.description && (
                              <p className="text-[11px] text-slate-500 leading-normal">{event.description}</p>
                            )}
                            <p className="text-[9px] text-slate-450 font-bold bg-white w-fit px-1.5 py-0.5 rounded-md border border-slate-100">
                              Duración: {formatTimeRange(event.starts_at, event.ends_at)}
                            </p>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                ) : (
                  <p className="text-xs text-slate-400 italic text-center py-12">
                    Seleccione un día del calendario para ver sus detalles.
                  </p>
                )}
              </div>
            </div>
          )}
        </div>
      )}
    </section>
  )
}
