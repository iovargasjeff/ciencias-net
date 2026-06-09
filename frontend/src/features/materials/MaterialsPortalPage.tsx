import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  Books,
  DownloadSimple,
  ArrowSquareOut,
  FileText,
  SpinnerGap,
  Warning,
  User,
  CalendarBlank,
  Funnel
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { getStudentSummary, listLinkedStudents, listAcademic } from '@/features/phase-one/api'
import { getApiError } from '@/lib/api/client'
import { listMaterials, downloadMaterial } from './api'

const contextKey = 'cienciasnet:selected-student-id'

export function MaterialsPortalPage() {
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

  // 1. Fetch student summary information (for sections/enrollments)
  const summaryQuery = useQuery({
    queryKey: ['student-summary', selectedId],
    queryFn: () => getStudentSummary(selectedId),
    enabled: Boolean(selectedId)
  })

  // 2. Fetch all academic metadata to map assignments
  const coursesQuery = useQuery({
    queryKey: ['academic', 'courses'],
    queryFn: () => listAcademic('courses'),
    enabled: !!selectedId
  })

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

  const assignmentsQuery = useQuery({
    queryKey: ['academic', 'assignments'],
    queryFn: () => listAcademic('teaching-assignments'),
    enabled: !!selectedId
  })

  // 3. Filters
  const [selectedWeek, setSelectedWeek] = useState<number | ''>('')
  const [selectedCourseId, setSelectedCourseId] = useState<string>('')

  // 4. Fetch materials filtering by student_id
  const { data: materialsData, isLoading: isLoadingMaterials } = useQuery({
    queryKey: ['portal-materials', selectedId],
    queryFn: () => listMaterials({ student_id: selectedId }),
    enabled: !!selectedId
  })

  if (!isParent && !isStudent) {
    return (
      <div className="flex flex-col items-center justify-center p-12 text-center bg-red-50 border border-red-100 rounded-3xl text-red-800">
        <Warning size={36} className="text-red-500" />
        <h3 className="text-base font-bold mt-2">Acceso Denegado</h3>
        <p className="text-xs text-red-650 mt-1">Su cuenta no tiene permisos para acceder al portal familiar o de alumnos.</p>
      </div>
    )
  }

  // Resolve matching sections and course assignments for Aislamiento por Matrícula
  const availableCourses = coursesQuery.data?.data || []
  const availableSections = sectionsQuery.data?.data || []
  const availableGrades = gradesQuery.data?.data || []
  const availableAssignments = assignmentsQuery.data?.data || []

  const getGradeName = (gradeId?: string) => {
    return availableGrades.find((g) => g.id === gradeId)?.name || ''
  }

  // Find sections matching the student's enrollments
  const enrolledSections = availableSections.filter((sec) => {
    return summaryQuery.data?.enrollments.some(
      (e) => e.section === sec.name && e.grade === getGradeName(sec.grade_id)
    )
  })

  const studentSectionIds = enrolledSections.map((s) => s.id)
  const studentAssignments = availableAssignments.filter((a) =>
    studentSectionIds.includes(a.section_id || '')
  )
  const studentAssignmentIds = studentAssignments.map((a) => a.id)

  // Mapping utilities
  const getCourseNameForAssignment = (assignmentId: string) => {
    const assign = availableAssignments.find((a) => a.id === assignmentId)
    const course = availableCourses.find((c) => c.id === assign?.course_id)
    return course?.name || 'Curso'
  }

  const getCourseIdForAssignment = (assignmentId: string) => {
    const assign = availableAssignments.find((a) => a.id === assignmentId)
    return assign?.course_id || ''
  }

  // Filter materials in frontend:
  // 1. MUST match the student's active assignments (Aislamiento por Matrícula)
  // 2. Filter by week if selected
  // 3. Filter by course if selected
  const rawMaterials = materialsData?.data || []
  const studentMaterials = rawMaterials.filter((material) => {
    // Aislamiento por Matrícula check
    const isAssigned = studentAssignmentIds.includes(material.teaching_assignment_id)
    if (!isAssigned) return false

    // Week check
    if (selectedWeek !== '' && material.week !== selectedWeek) return false

    // Course check
    if (selectedCourseId !== '') {
      const courseId = getCourseIdForAssignment(material.teaching_assignment_id)
      if (courseId !== selectedCourseId) return false
    }

    return true
  })

  // Sort materials: Week first (ascending), then updated_at (newest first)
  const sortedMaterials = [...studentMaterials].sort((a, b) => {
    const wA = a.week ?? 999
    const wB = b.week ?? 999
    if (wA !== wB) return wA - wB
    return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
  })

  // List of weeks actually present in the student's materials
  const availableWeeks = Array.from(
    new Set(rawMaterials.filter(m => studentAssignmentIds.includes(m.teaching_assignment_id) && m.week !== null).map(m => m.week as number))
  ).sort((a, b) => a - b)

  // List of courses active in student's assignments
  const studentCourses = availableCourses.filter(c =>
    studentAssignments.some(a => a.course_id === c.id)
  )

  const handleDownload = async (materialId: string, fileName: string | null) => {
    try {
      const blob = await downloadMaterial(materialId)
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = fileName || 'material-descargado'
      document.body.appendChild(a)
      a.click()
      a.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      alert('Error al descargar el archivo: ' + getApiError(err).message)
    }
  }

  const formatBytes = (bytes: number | null) => {
    if (bytes === null || bytes === undefined) return ''
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  // Pre-generate week array from 1 to 53 for filter selector
  const weeks1to53 = Array.from({ length: 53 }, (_, i) => i + 1)

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Top Banner with Student Selector for Parents */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Books className="text-blue-600" size={24} weight="duotone" />
            Materiales de Estudio
          </h2>
          <p className="text-xs text-slate-500">Revise y descargue las guías, tareas y enlaces publicados por sus docentes.</p>
        </div>

        {isParent && (studentsQuery.data?.length || 0) > 1 && (
          <div className="w-full md:w-64">
            <label htmlFor="student-select" className="text-[10px] font-extrabold text-slate-400 block mb-1 uppercase tracking-wider">Hijo / Alumno</label>
            <div className="relative">
              <select
                id="student-select"
                className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-850 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none pr-8"
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

      {/* Loading active student info */}
      {summaryQuery.isLoading || isLoadingMaterials ? (
        <div className="flex flex-col items-center justify-center py-16">
          <SpinnerGap className="spin text-blue-500" size={36} />
          <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando portal de materiales...</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          {/* Filters Sidebar */}
          <div className="lg:col-span-1 space-y-4">
            <div className="bg-white border border-slate-100/80 p-5 rounded-3xl shadow-sm space-y-5">
              <h3 className="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
                <Funnel size={14} weight="bold" />
                Filtros
              </h3>

              {/* Course filter */}
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Curso</label>
                <select
                  className="w-full p-2 rounded-lg border border-slate-200 text-xs font-semibold bg-slate-50 text-slate-800"
                  value={selectedCourseId}
                  onChange={(e) => setSelectedCourseId(e.target.value)}
                >
                  <option value="">Todos los cursos</option>
                  {studentCourses.map(course => (
                    <option key={course.id} value={course.id}>{course.name}</option>
                  ))}
                </select>
              </div>

              {/* Week filter (combobox) */}
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Línea de Tiempo (Semana)</label>
                <select
                  className="w-full p-2 rounded-lg border border-slate-200 text-xs font-semibold bg-slate-50 text-slate-800"
                  value={selectedWeek}
                  onChange={(e) => setSelectedWeek(e.target.value === '' ? '' : Number(e.target.value))}
                >
                  <option value="">Todas las semanas</option>
                  {weeks1to53.map(w => (
                    <option key={w} value={w}>Semana {w}</option>
                  ))}
                </select>
              </div>

              {/* Quick links to active weeks */}
              {availableWeeks.length > 0 && (
                <div className="space-y-2 pt-2 border-t border-slate-50">
                  <h4 className="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Semanas con Materiales</h4>
                  <div className="flex flex-wrap gap-1.5">
                    <button
                      onClick={() => setSelectedWeek('')}
                      className={`px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all ${selectedWeek === '' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-650 hover:bg-slate-200/60'}`}
                    >
                      Todas
                    </button>
                    {availableWeeks.map(w => (
                      <button
                        key={w}
                        onClick={() => setSelectedWeek(w)}
                        className={`px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all ${selectedWeek === w ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-650 hover:bg-slate-200/60'}`}
                      >
                        Sem. {w}
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Materials Grid */}
          <div className="lg:col-span-3 space-y-4">
            {sortedMaterials.length === 0 ? (
              <div className="flex flex-col items-center justify-center p-16 text-center bg-white border border-slate-100 rounded-3xl min-h-[300px]">
                <div className="p-3.5 bg-slate-50 rounded-full text-slate-400 mb-3">
                  <FileText size={28} />
                </div>
                <h3 className="text-sm font-bold text-slate-800">No se encontraron materiales</h3>
                <p className="text-xs text-slate-500 max-w-xs mt-1 leading-relaxed">No hay guías ni enlaces cargados que coincidan con la sección de matrícula o con los filtros seleccionados.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {sortedMaterials.map((material) => {
                  const courseName = getCourseNameForAssignment(material.teaching_assignment_id)
                  return (
                    <div
                      key={material.id}
                      className="bg-white border border-slate-100/90 hover:border-slate-200/80 p-5 rounded-2xl shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4"
                    >
                      <div className="space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[9px] font-extrabold tracking-wider uppercase">
                            Semana {material.week || 'N/A'}
                          </span>
                          <span className="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                            <CalendarBlank size={12} />
                            {new Date(material.created_at).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                          </span>
                        </div>

                        <div>
                          <p className="text-[10px] font-bold text-blue-600 tracking-wide uppercase">{courseName}</p>
                          <h4 className="text-sm font-bold text-slate-900 mt-0.5 line-clamp-1">{material.title}</h4>
                          {material.description && (
                            <p className="text-xs text-slate-550 mt-1 line-clamp-2 leading-relaxed">
                              {material.description}
                            </p>
                          )}
                        </div>
                      </div>

                      <div className="pt-2 border-t border-slate-50 flex items-center justify-between">
                        {material.type === 'file' ? (
                          <>
                            <div className="min-w-0 flex-1 pr-2">
                              <p className="text-[10px] text-slate-400 font-mono truncate">{material.file_name}</p>
                              <p className="text-[9px] text-slate-400 font-mono mt-0.5">{formatBytes(material.file_size)}</p>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleDownload(material.id, material.file_name)}
                              className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-3.5 py-2 font-bold flex items-center gap-1.5 shadow-sm transition-all"
                            >
                              <DownloadSimple size={14} weight="bold" /> Descargar
                            </button>
                          </>
                        ) : (
                          <>
                            <div className="min-w-0 flex-1 pr-2">
                              <p className="text-[10px] text-slate-400 truncate">{material.url}</p>
                            </div>
                            <a
                              href={material.url || '#'}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="button button-secondary border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs px-3.5 py-2 font-bold flex items-center gap-1.5 transition-all"
                            >
                              Abrir Enlace <ArrowSquareOut size={14} weight="bold" />
                            </a>
                          </>
                        )}
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </div>
      )}
    </section>
  )
}
