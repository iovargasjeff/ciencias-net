import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueries, useQuery, useQueryClient } from '@tanstack/react-query'
import { useEffect, useMemo, useState } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { useAuth } from '@/features/auth/AuthContext'
import { getApiError } from '@/lib/api/client'
import { createAcademic, deleteAcademic, listAcademic, listAccounts, searchDni, updateAcademic } from './api'
import type { AcademicPath } from './api'
import type { AcademicItem, Account } from './types'

const entities: Array<{ path: AcademicPath; label: string; help: string }> = [
  { path: 'academic-periods', label: 'Periodos', help: 'Anos academicos y vigencia' },
  { path: 'grades', label: 'Grados', help: 'Nivel y orden por periodo' },
  { path: 'sections', label: 'Secciones', help: 'Aulas dependientes de grado' },
  { path: 'courses', label: 'Cursos', help: 'Catalogo curricular por grado' },
  { path: 'enrollments', label: 'Matriculas', help: 'Alumno, grado, seccion, periodo y cursos' },
  { path: 'teaching-assignments', label: 'Carga docente', help: 'Docente, curso y seccion' },
]

const schema = z.object({
  entity: z.enum(['academic-periods', 'grades', 'sections', 'courses', 'enrollments', 'teaching-assignments']),
  name: z.string().optional(),
  code: z.string().optional(),
  start_date: z.string().optional(),
  end_date: z.string().optional(),
  level: z.string().optional(),
  order: z.string().optional(),
  capacity: z.string().optional(),
  academic_period_id: z.string().optional(),
  grade_id: z.string().optional(),
  student_id: z.string().optional(),
  section_id: z.string().optional(),
  teacher_id: z.string().optional(),
  course_id: z.string().optional(),
  course_ids: z.array(z.string()).optional(),
})

type Form = z.infer<typeof schema>
type PersonLookup = { id: string; dni?: string; name: string; email?: string } | null

function payload(values: Form): Record<string, unknown> {
  const common: Record<string, unknown> = Object.fromEntries(
    Object.entries(values).filter(([key, value]) => key !== 'entity' && value && (!Array.isArray(value) || value.length > 0)),
  )
  if (values.order) common.order = Number(values.order)
  if (values.capacity) common.capacity = Number(values.capacity)
  if (values.entity === 'academic-periods') common.status = 'draft'
  if (values.entity === 'enrollments') common.enrolled_at = new Date().toISOString().slice(0, 10)
  return common
}

function itemName(items: AcademicItem[], id?: string) {
  return items.find((item) => item.id === id)?.name ?? id ?? 'No registrado'
}

function gradeLabel(grades: AcademicItem[], id?: string) {
  return itemName(grades, id)
}

export function AcademicAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()
  const queries = useQueries({
    queries: entities.map(({ path }) => ({ queryKey: ['academic', path], queryFn: () => listAcademic(path) })),
  })
  const accounts = useQuery({ queryKey: ['accounts', 'academic-people'], queryFn: () => listAccounts('', 'superadmin') })
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { entity: 'academic-periods', course_ids: [] } })
  const entity = useWatch({ control: form.control, name: 'entity' })
  const selectedPeriodId = useWatch({ control: form.control, name: 'academic_period_id' })
  const selectedGradeId = useWatch({ control: form.control, name: 'grade_id' })
  const selectedSectionId = useWatch({ control: form.control, name: 'section_id' })
  const selectedStudentId = useWatch({ control: form.control, name: 'student_id' })
  const selectedTeacherId = useWatch({ control: form.control, name: 'teacher_id' })
  const selectedCourseId = useWatch({ control: form.control, name: 'course_id' })
  const selectedCourseIds = useWatch({ control: form.control, name: 'course_ids' }) ?? []

  const [studentSearch, setStudentSearch] = useState('')
  const [enrollmentSearch, setEnrollmentSearch] = useState('')
  const [enrollmentGradeFilter, setEnrollmentGradeFilter] = useState('')
  const [enrollmentSectionFilter, setEnrollmentSectionFilter] = useState('')
  const [studentResult, setStudentResult] = useState<PersonLookup>(null)
  const [studentStatus, setStudentStatus] = useState('')
  const [teacherSearch, setTeacherSearch] = useState('')
  const [teacherResult, setTeacherResult] = useState<PersonLookup>(null)
  const [teacherStatus, setTeacherStatus] = useState('')
  const [successMsg, setSuccessMsg] = useState('')
  const [editTarget, setEditTarget] = useState<{ path: AcademicPath; id: string } | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<{ path: AcademicPath; id: string; label: string } | null>(null)
  const [detailEnrollment, setDetailEnrollment] = useState<AcademicItem | null>(null)

  const periods = useMemo(() => queries[0].data?.data ?? [], [queries])
  const grades = useMemo(() => queries[1].data?.data ?? [], [queries])
  const sections = useMemo(() => queries[2].data?.data ?? [], [queries])
  const courses = useMemo(() => queries[3].data?.data ?? [], [queries])
  const enrollments = useMemo(() => queries[4].data?.data ?? [], [queries])
  const assignments = useMemo(() => queries[5].data?.data ?? [], [queries])
  const studentAccounts = useMemo(
    () => accounts.data?.data.filter((account) => account.roles.includes('alumno')) ?? [],
    [accounts.data?.data],
  )
  const canEditAcademic = user?.roles.some((role) => ['superadmin', 'coordinador_academico'].includes(role)) === true

  const filteredSections = useMemo(
    () => sections.filter((section) => !selectedGradeId || section.grade_id === selectedGradeId),
    [sections, selectedGradeId],
  )
  const filteredCourses = useMemo(
    () => courses.filter((course) => !selectedGradeId || !course.grade_id || course.grade_id === selectedGradeId),
    [courses, selectedGradeId],
  )
  const periodScopedGrades = useMemo(
    () => grades.filter((grade) => !selectedPeriodId || grade.academic_period_id === selectedPeriodId),
    [grades, selectedPeriodId],
  )
  const formSectionCourses = useMemo(() => {
    const byCourse = new Map<string, AcademicItem>()
    assignments
      .filter((assignment) => assignment.section_id === selectedSectionId)
      .forEach((assignment) => {
        const course = courses.find((item) => item.id === assignment.course_id)
        if (course) byCourse.set(course.id, course)
      })
    return [...byCourse.values()]
  }, [assignments, courses, selectedSectionId])
  const enrollmentFilterSections = useMemo(
    () => sections.filter((section) => !enrollmentGradeFilter || section.grade_id === enrollmentGradeFilter),
    [enrollmentGradeFilter, sections],
  )
  const filteredEnrollments = useMemo(() => {
    const search = enrollmentSearch.trim().toLowerCase()
    return enrollments.filter((enrollment) => {
      const matchesGrade = !enrollmentGradeFilter || enrollment.grade_id === enrollmentGradeFilter
      const matchesSection = !enrollmentSectionFilter || enrollment.section_id === enrollmentSectionFilter
      const courseText = enrollment.courses?.map((course) => `${course.name ?? ''} ${course.code ?? ''}`).join(' ') ?? ''
      const matchesSearch =
        !search ||
        enrollment.student_name?.toLowerCase().includes(search) ||
        enrollment.student_dni?.toLowerCase().includes(search) ||
        enrollment.name?.toLowerCase().includes(search) ||
        courseText.toLowerCase().includes(search)
      return matchesGrade && matchesSection && matchesSearch
    })
  }, [enrollmentGradeFilter, enrollmentSearch, enrollmentSectionFilter, enrollments])

  function changeEntity(nextEntity: AcademicPath) {
    form.reset({ entity: nextEntity, course_ids: [] })
    setStudentSearch('')
    setEnrollmentSearch('')
    setStudentResult(null)
    setStudentStatus('')
    setTeacherSearch('')
    setTeacherResult(null)
    setTeacherStatus('')
    setSuccessMsg('')
    setEditTarget(null)
    setDeleteTarget(null)
    setDetailEnrollment(null)
  }

  useEffect(() => {
    if (selectedGradeId && selectedSectionId && !filteredSections.some((section) => section.id === selectedSectionId)) {
      form.setValue('section_id', '')
    }
  }, [filteredSections, form, selectedGradeId, selectedSectionId])

  useEffect(() => {
    if (selectedGradeId && selectedCourseId && !filteredCourses.some((course) => course.id === selectedCourseId)) {
      form.setValue('course_id', '')
    }
  }, [filteredCourses, form, selectedCourseId, selectedGradeId])

  useEffect(() => {
    if (!['enrollments', 'teaching-assignments'].includes(entity) || !selectedPeriodId || !selectedGradeId) return
    if (!periodScopedGrades.some((grade) => grade.id === selectedGradeId)) {
      form.setValue('grade_id', '')
      form.setValue('section_id', '')
      form.setValue('course_id', '')
      form.setValue('course_ids', [])
    }
  }, [entity, form, periodScopedGrades, selectedGradeId, selectedPeriodId])



  useEffect(() => {
    if (entity !== 'enrollments' || !selectedSectionId) return
    const ids = formSectionCourses.map((course) => course.id)
    form.setValue('course_ids', ids)
  }, [entity, form, formSectionCourses, selectedSectionId])

  async function lookupStudent() {
    const search = studentSearch.trim()
    setStudentStatus('')
    setStudentResult(null)
    form.setValue('student_id', '')
    if (search.length < 3) {
      setStudentStatus('Ingresa al menos 3 caracteres o un DNI completo.')
      return
    }
    if (!/^\d{8,15}$/.test(search)) {
      const matches = await listAccounts(search, 'superadmin')
      const student = matches.data.find((account: Account) => account.roles.includes('alumno'))
      if (!student) {
        setStudentStatus('No se encontro un alumno con ese nombre o correo.')
        return
      }
      setStudentResult({ id: student.id, name: student.name, email: student.email })
      form.setValue('student_id', student.id)
      return
    }
    const result = await searchDni('students', search)
    if (!result) {
      setStudentStatus('No se encontro un alumno con ese DNI.')
      return
    }
    setStudentResult(result)
    form.setValue('student_id', result.id)
  }

  async function lookupTeacher() {
    const search = teacherSearch.trim()
    setTeacherStatus('')
    setTeacherResult(null)
    form.setValue('teacher_id', '')
    if (!/^\d{8,15}$/.test(search)) {
      setTeacherStatus('Ingresa el DNI del docente para seleccionarlo.')
      return
    }
    const result = await searchDni('teachers', search)
    if (!result) {
      setTeacherStatus('No se encontro un docente con ese DNI.')
      return
    }
    setTeacherResult(result)
    form.setValue('teacher_id', result.id)
  }

  const create = useMutation({
    mutationFn: (values: Form) => {
      if (editTarget) return updateAcademic(editTarget.path, editTarget.id, payload(values))
      return createAcademic(values.entity, payload(values))
    },
    onSuccess: async () => {
      const wasEditing = editTarget !== null
      const currentEntity = form.getValues('entity')
      form.reset({ entity: currentEntity, course_ids: [] })
      setStudentSearch('')
      setStudentResult(null)
      setTeacherSearch('')
      setTeacherResult(null)
      setEditTarget(null)
      setSuccessMsg(wasEditing ? 'Cambios guardados correctamente.' : 'Registro creado exitosamente.')
      setTimeout(() => setSuccessMsg(''), 3000)
      await client.invalidateQueries({ queryKey: ['academic'] })
    },
  })

  const destroy = useMutation({
    mutationFn: ({ path, id }: { path: AcademicPath; id: string }) => deleteAcademic(path, id),
    onSuccess: async () => {
      setDeleteTarget(null)
      setSuccessMsg('Registro eliminado correctamente.')
      setTimeout(() => setSuccessMsg(''), 3000)
      await client.invalidateQueries({ queryKey: ['academic'] })
    },
  })

  const detachCourse = useMutation({
    mutationFn: ({ enrollment, courseId }: { enrollment: AcademicItem; courseId: string }) => {
      const nextCourseIds = (enrollment.course_ids ?? []).filter((id) => id !== courseId)
      return updateAcademic('enrollments', enrollment.id, { course_ids: nextCourseIds })
    },
    onSuccess: async (updated) => {
      setDetailEnrollment(updated)
      setSuccessMsg('Curso retirado de la matricula correctamente.')
      setTimeout(() => setSuccessMsg(''), 3000)
      await client.invalidateQueries({ queryKey: ['academic'] })
    },
  })

  const activeEntity = entities.find((item) => item.path === entity) ?? entities[0]
  const activeIndex = entities.findIndex((item) => item.path === entity)
  const canSubmit =
    canEditAcademic &&
    !create.isPending &&
    !(entity === 'courses' && !selectedGradeId) &&
    !(entity === 'enrollments' && (!selectedStudentId || !selectedSectionId || selectedCourseIds.length === 0)) &&
    !(entity === 'teaching-assignments' && (!selectedTeacherId || !selectedCourseId || !selectedSectionId))

  return (
    <section className="page-stack">
      <header>
        <p className="eyebrow">Estructura academica</p>
        <h1>Coordinacion academica</h1>
        <p>Gestiona periodos, grados, secciones, cursos, matriculas y carga docente en vistas separadas.</p>
      </header>

      <nav className="tabs" aria-label="Vistas academicas" role="tablist">
        {entities.map((item) => (
          <button
            key={item.path}
            type="button"
            role="tab"
            aria-selected={entity === item.path}
            className={`tab ${entity === item.path ? 'tab-active' : ''}`}
            onClick={() => changeEntity(item.path)}
          >
            <span>{item.label}</span>
            <small>{item.help}</small>
          </button>
        ))}
      </nav>

      <form className="panel form-grid" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
        <h2>{editTarget ? 'Editar' : 'Crear'} {activeEntity.label.toLowerCase()}</h2>
        <input type="hidden" {...form.register('entity')} />

        {['academic-periods', 'grades', 'sections', 'courses'].includes(entity) && (
          <label>Nombre<input {...form.register('name')} required /></label>
        )}

        {entity === 'academic-periods' && (
          <>
            <label>Inicio<input type="date" {...form.register('start_date')} required /></label>
            <label>Fin<input type="date" {...form.register('end_date')} required /></label>
          </>
        )}

        {entity === 'grades' && (
          <>
            <label>Nivel<select {...form.register('level')}><option>secundaria</option></select></label>
            <label>Orden<input type="number" min="1" {...form.register('order')} required /></label>
            <label>Periodo<select {...form.register('academic_period_id')} required><option value="">Selecciona periodo</option>{periods.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
          </>
        )}

        {entity === 'sections' && (
          <>
            <label>Grado<select {...form.register('grade_id')} required><option value="">Selecciona grado</option>{grades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Capacidad<input type="number" min="1" {...form.register('capacity')} required /></label>
          </>
        )}

        {entity === 'courses' && (
          <>
            <label>Grado<select {...form.register('grade_id')} required><option value="">Selecciona grado</option>{grades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Codigo<input {...form.register('code')} required /></label>
          </>
        )}

        {entity === 'enrollments' && (
          <>
            <label>Periodo<select {...form.register('academic_period_id')} required><option value="">Selecciona periodo</option>{periods.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Grado<select {...form.register('grade_id')} required disabled={!selectedPeriodId}><option value="">Selecciona grado</option>{periodScopedGrades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Seccion<select {...form.register('section_id')} required disabled={!selectedGradeId}><option value="">Selecciona seccion</option>{filteredSections.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Alumno por DNI o nombre
              <span className="inline-search">
                <input value={studentSearch} onChange={(event) => setStudentSearch(event.target.value)} placeholder="DNI, nombre o correo" list="academic-student-options" />
                <button type="button" className="button" onClick={lookupStudent}>Buscar</button>
              </span>
              <datalist id="academic-student-options">
                {studentAccounts.map((student) => <option key={student.id} value={student.name}>{student.email}</option>)}
              </datalist>
              {studentResult && <small className="form-success">{studentResult.name}{studentResult.dni ? ` - DNI ${studentResult.dni}` : ` - ${studentResult.email ?? 'cuenta seleccionada'}`}</small>}
              {studentStatus && <small className="form-error">{studentStatus}</small>}
            </label>
            <fieldset className="course-picker">
              <legend>Cursos de la seccion</legend>
              {formSectionCourses.length > 0 ? formSectionCourses.map((course) => (
                <label key={course.id} className="check-pill">
                  <input
                    type="checkbox"
                    checked={selectedCourseIds.includes(course.id)}
                    onChange={(event) => {
                      const next = event.target.checked
                        ? [...selectedCourseIds, course.id]
                        : selectedCourseIds.filter((id) => id !== course.id)
                      form.setValue('course_ids', next, { shouldDirty: true })
                    }}
                  />
                  <span>{course.name}<small>{course.code}</small></span>
                </label>
              )) : <p className="form-help">Selecciona una seccion con carga docente para ver cursos disponibles.</p>}
            </fieldset>
          </>
        )}

        {entity === 'teaching-assignments' && (
          <>
            <label>Periodo<select {...form.register('academic_period_id')} required><option value="">Selecciona periodo</option>{periods.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Grado<select {...form.register('grade_id')} required disabled={!selectedPeriodId}><option value="">Selecciona grado</option>{periodScopedGrades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Seccion<select {...form.register('section_id')} required disabled={!selectedGradeId}><option value="">Selecciona seccion</option>{filteredSections.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Curso<select {...form.register('course_id')} required disabled={!selectedGradeId}><option value="">Selecciona curso</option>{filteredCourses.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Docente por DNI
              <span className="inline-search">
                <input value={teacherSearch} onChange={(event) => setTeacherSearch(event.target.value)} placeholder="DNI docente" />
                <button type="button" className="button" onClick={lookupTeacher}>Buscar</button>
              </span>
              {teacherResult && <small className="form-success">{teacherResult.name} - DNI {teacherResult.dni}</small>}
              {teacherStatus && <small className="form-error">{teacherStatus}</small>}
            </label>
          </>
        )}

        {editTarget && (
          <div className="edit-notice">
            <strong>Modo edicion activo</strong>
            <span>Revisa los campos y guarda los cambios del registro seleccionado.</span>
          </div>
        )}
        <div className="form-actions">
          <button className="button button-primary" disabled={!canSubmit}>{editTarget ? 'Guardar cambios' : 'Crear'}</button>
          {editTarget && <button type="button" className="button button-ghost" onClick={() => { form.reset({ entity, course_ids: [] }); setEditTarget(null) }}>Cancelar edicion</button>}
        </div>
        {!canEditAcademic && <p className="form-error">Tu cuenta no tiene permisos de edicion academica.</p>}
        {!canSubmit && entity === 'enrollments' && <p className="form-help">Selecciona grado, seccion, alumno y al menos un curso antes de matricular.</p>}
        {!canSubmit && entity === 'courses' && <p className="form-help">Selecciona el grado al que pertenece el curso.</p>}
        {!canSubmit && entity === 'teaching-assignments' && <p className="form-help">Selecciona grado, seccion, curso y docente antes de guardar.</p>}
        {successMsg && <p className="form-success">{successMsg}</p>}
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>

      <article className="panel">
        <header className="panel-header">
          <div>
            <h2>{activeEntity.label}</h2>
            <p>{activeEntity.help}</p>
          </div>
          {entity === 'enrollments' && (
            <div className="table-controls">
              <select className="glass-input-light" value={enrollmentGradeFilter} onChange={(event) => {
                setEnrollmentGradeFilter(event.target.value)
                setEnrollmentSectionFilter('')
              }}>
                <option value="">Todos los grados</option>
                {grades.map((grade) => <option key={grade.id} value={grade.id}>{grade.name}</option>)}
              </select>
              <select className="glass-input-light" value={enrollmentSectionFilter} onChange={(event) => setEnrollmentSectionFilter(event.target.value)} disabled={!enrollmentGradeFilter}>
                <option value="">Todas las secciones</option>
                {enrollmentFilterSections.map((section) => <option key={section.id} value={section.id}>{section.name}</option>)}
              </select>
              <input
                className="glass-input-light"
                value={enrollmentSearch}
                onChange={(event) => setEnrollmentSearch(event.target.value)}
                placeholder="Filtrar por alumno, DNI o curso"
              />
              <span className="badge">{filteredEnrollments.length} matriculas filtradas</span>
            </div>
          )}
        </header>
        <DataTable
          rows={entity === 'enrollments' ? filteredEnrollments : queries[activeIndex].data?.data}
          isLoading={queries[activeIndex].isLoading}
          error={queries[activeIndex].error}
          columns={[
            {
              label: entity === 'enrollments' ? 'Alumno' : 'Registro',
              render: (row) => entity === 'enrollments' ? (row.student_name ?? row.name ?? row.id) : (row.name ?? row.code ?? row.id),
            },
            {
              label: 'Detalle',
              render: (row) => {
                if (entity === 'sections') return `Grado: ${gradeLabel(grades, row.grade_id)}`
                if (entity === 'courses') return `Grado: ${gradeLabel(grades, row.grade_id)}`
                if (entity === 'enrollments') {
                  const courseCount = row.courses?.length ?? 0
                  return `${row.grade_name ?? itemName(grades, row.grade_id)} ${row.section_name ?? itemName(sections, row.section_id)} | ${row.period_name ?? itemName(periods, row.academic_period_id)} | ${courseCount} cursos`
                }
                if (entity === 'teaching-assignments') {
                  const validity = row.valid_until ? ` | Historica hasta ${row.valid_until}` : ''
                  return `${itemName(courses, row.course_id)} | ${itemName(sections, row.section_id)}${validity}`
                }
                return row.status ?? row.level ?? row.valid_from ?? 'Vigente'
              },
            },
            {
              label: 'Acciones',
              render: (row) => (
                <span className="row-actions">
                  {entity === 'enrollments' && (
                    <button type="button" onClick={() => setDetailEnrollment(row)} className="button button-secondary">
                      Ver detalles
                    </button>
                  )}
                  <button
                    type="button"
                    onClick={() => {
                      setEditTarget({ path: entity, id: row.id })
                      setStudentResult(row.student_name ? { id: row.student_id ?? '', name: row.student_name, dni: row.student_dni } : null)
                      form.reset({
                        entity,
                        name: row.name,
                        code: row.code,
                        level: row.level,
                        grade_id: row.grade_id,
                        section_id: row.section_id,
                        course_id: row.course_id,
                        course_ids: row.course_ids ?? [],
                        teacher_id: row.teacher_id,
                        student_id: row.student_id,
                        academic_period_id: row.academic_period_id,
                      })
                    }}
                    className="button button-edit"
                    disabled={!canEditAcademic}
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    onClick={() => setDeleteTarget({ path: entity, id: row.id, label: row.name ?? row.code ?? row.id })}
                    className="button button-danger"
                    disabled={!canEditAcademic || destroy.isPending}
                  >
                    Eliminar
                  </button>
                </span>
              ),
            },
          ]}
        />
        {entity === 'teaching-assignments' && assignments.length === 0 && <p className="empty-state">No hay cargas docentes registradas.</p>}
      </article>

      {detailEnrollment && (
        <div className="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="enrollment-detail-title">
          <div className="confirm-dialog detail-dialog">
            <div>
              <p className="eyebrow">Detalle de matricula</p>
              <h2 id="enrollment-detail-title">{detailEnrollment.student_name ?? detailEnrollment.name}</h2>
              <p>{detailEnrollment.grade_name} {detailEnrollment.section_name} | {detailEnrollment.period_name} | DNI {detailEnrollment.student_dni ?? 'no registrado'}</p>
            </div>
            <div className="course-list">
              {(detailEnrollment.courses ?? []).map((course) => {
                const canRemove = (detailEnrollment.course_ids?.length ?? 0) > 1
                return (
                  <div key={course.assignment_id} className="course-row">
                    <span><strong>{course.name}</strong><small>{course.code} | Docente: {course.teacher ?? 'No asignado'}</small></span>
                    <button
                      type="button"
                      className="button button-danger"
                      disabled={!canEditAcademic || !canRemove || detachCourse.isPending}
                      onClick={() => detachCourse.mutate({ enrollment: detailEnrollment, courseId: course.course_id })}
                    >
                      Quitar curso
                    </button>
                  </div>
                )
              })}
            </div>
            {!detailEnrollment.courses?.length && <p className="form-help">No hay cursos vinculados a esta matricula.</p>}
            {detachCourse.error && <p className="form-error">{getApiError(detachCourse.error).message}</p>}
            <div className="form-actions">
              <button type="button" className="button button-ghost" onClick={() => setDetailEnrollment(null)}>Cerrar</button>
            </div>
          </div>
        </div>
      )}

      {deleteTarget && (
        <div className="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-academic-title">
          <div className="confirm-dialog">
            <div className="confirm-icon">!</div>
            <div>
              <p className="eyebrow">Confirmar eliminacion</p>
              <h2 id="delete-academic-title">Eliminar registro academico</h2>
              <p>Esta accion retirara <strong>{deleteTarget.label}</strong> de la vista actual. Confirma solo si el registro ya no debe estar disponible.</p>
            </div>
            {destroy.error && <p className="form-error">{getApiError(destroy.error).message}</p>}
            <div className="form-actions">
              <button type="button" className="button button-ghost" onClick={() => setDeleteTarget(null)} disabled={destroy.isPending}>Cancelar</button>
              <button type="button" className="button button-danger" onClick={() => destroy.mutate({ path: deleteTarget.path, id: deleteTarget.id })} disabled={destroy.isPending}>
                {destroy.isPending ? 'Eliminando...' : 'Si, eliminar'}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
