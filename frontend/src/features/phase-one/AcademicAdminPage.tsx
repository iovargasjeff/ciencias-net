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
  { path: 'courses', label: 'Cursos', help: 'Catalogo curricular' },
  { path: 'enrollments', label: 'Matriculas', help: 'Alumno, grado, seccion y periodo' },
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
})

type Form = z.infer<typeof schema>
type PersonLookup = { id: string; dni?: string; name: string; email?: string } | null

function payload(values: Form): Record<string, unknown> {
  const common: Record<string, unknown> = Object.fromEntries(
    Object.entries(values).filter(([key, value]) => key !== 'entity' && value),
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

export function AcademicAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()
  const queries = useQueries({
    queries: entities.map(({ path }) => ({ queryKey: ['academic', path], queryFn: () => listAcademic(path) })),
  })
  const accounts = useQuery({ queryKey: ['accounts', 'academic-people'], queryFn: () => listAccounts('', 'superadmin') })
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { entity: 'academic-periods' } })
  const entity = useWatch({ control: form.control, name: 'entity' })
  const selectedGradeId = useWatch({ control: form.control, name: 'grade_id' })
  const selectedSectionId = useWatch({ control: form.control, name: 'section_id' })
  const selectedStudentId = useWatch({ control: form.control, name: 'student_id' })
  const selectedTeacherId = useWatch({ control: form.control, name: 'teacher_id' })
  const selectedCourseId = useWatch({ control: form.control, name: 'course_id' })

  const [studentSearch, setStudentSearch] = useState('')
  const [enrollmentSearch, setEnrollmentSearch] = useState('')
  const [studentResult, setStudentResult] = useState<PersonLookup>(null)
  const [studentStatus, setStudentStatus] = useState('')
  const [teacherSearch, setTeacherSearch] = useState('')
  const [teacherResult, setTeacherResult] = useState<PersonLookup>(null)
  const [teacherStatus, setTeacherStatus] = useState('')
  const [successMsg, setSuccessMsg] = useState('')
  const [editTarget, setEditTarget] = useState<{ path: AcademicPath; id: string } | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<{ path: AcademicPath; id: string; label: string } | null>(null)

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
  const filteredEnrollments = useMemo(
    () => {
      const gradeSectionIds = new Set(filteredSections.map((section) => section.id))
      const search = enrollmentSearch.trim().toLowerCase()
      return enrollments.filter((enrollment) => {
        const student = studentAccounts.find((account) => account.id === enrollment.student_id)
        const matchesGrade = !selectedGradeId || (enrollment.section_id ? gradeSectionIds.has(enrollment.section_id) : false)
        const matchesSection = !selectedSectionId || enrollment.section_id === selectedSectionId
        const matchesSearch =
          !search ||
          enrollment.student_id?.toLowerCase().includes(search) ||
          student?.name.toLowerCase().includes(search) ||
          student?.email.toLowerCase().includes(search)
        return matchesGrade && matchesSection && matchesSearch
      })
    },
    [enrollmentSearch, enrollments, filteredSections, selectedGradeId, selectedSectionId, studentAccounts],
  )

  function changeEntity(nextEntity: AcademicPath) {
    form.reset({ entity: nextEntity })
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
      form.reset({ entity: currentEntity })
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

  const activeEntity = entities.find((item) => item.path === entity) ?? entities[0]
  const activeIndex = entities.findIndex((item) => item.path === entity)
  const canDeleteEntity = entity !== 'academic-periods'
  const canSubmit =
    canEditAcademic &&
    !create.isPending &&
    !(entity === 'enrollments' && (!selectedStudentId || !selectedSectionId)) &&
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
            <label>Nivel<select {...form.register('level')}><option>inicial</option><option>primaria</option><option>secundaria</option></select></label>
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

        {entity === 'courses' && <label>Codigo<input {...form.register('code')} required /></label>}

        {entity === 'enrollments' && (
          <>
            <label>Grado<select {...form.register('grade_id')} required><option value="">Selecciona grado</option>{grades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Seccion<select {...form.register('section_id')} required disabled={!selectedGradeId}><option value="">Selecciona seccion</option>{filteredSections.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Periodo<select {...form.register('academic_period_id')} required><option value="">Selecciona periodo</option>{periods.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
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
          </>
        )}

        {entity === 'teaching-assignments' && (
          <>
            <label>Grado<select {...form.register('grade_id')} required><option value="">Selecciona grado</option>{grades.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Seccion<select {...form.register('section_id')} required disabled={!selectedGradeId}><option value="">Selecciona seccion</option>{filteredSections.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Curso<select {...form.register('course_id')} required disabled={!selectedGradeId}><option value="">Selecciona curso</option>{filteredCourses.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            <label>Periodo<select {...form.register('academic_period_id')} required><option value="">Selecciona periodo</option>{periods.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
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
          {editTarget && <button type="button" className="button button-ghost" onClick={() => { form.reset({ entity }); setEditTarget(null) }}>Cancelar edicion</button>}
        </div>
        {!canEditAcademic && <p className="form-error">Tu cuenta no tiene permisos de edicion academica.</p>}
        {!canSubmit && entity === 'enrollments' && <p className="form-help">Selecciona grado, seccion y alumno antes de matricular.</p>}
        {!canSubmit && entity === 'teaching-assignments' && <p className="form-help">Selecciona grado, seccion, curso y docente antes de guardar.</p>}
        {successMsg && <p className="form-success">{successMsg}</p>}
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>

      <article className="panel">
        <header className="panel-header">
          <div>
            <h2>{activeEntity.label}</h2>
            <p>{activeEntity.help}</p>
            {entity === 'academic-periods' && <small className="form-help">Los periodos se conservan para trazabilidad historica; usa Editar para ajustar vigencia o estado.</small>}
          </div>
          {entity === 'enrollments' && (
            <div className="table-controls">
              <input
                className="glass-input-light"
                value={enrollmentSearch}
                onChange={(event) => setEnrollmentSearch(event.target.value)}
                placeholder="Filtrar por nombre, DNI, correo o codigo"
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
            { label: 'Registro', render: (row) => row.name ?? row.code ?? row.id },
            {
              label: 'Detalle',
              render: (row) => {
                if (entity === 'sections') return `Grado: ${itemName(grades, row.grade_id)}`
                if (entity === 'enrollments') {
                  const student = studentAccounts.find((account) => account.id === row.student_id)
                  return `Seccion: ${itemName(sections, row.section_id)} | Alumno: ${student?.name ?? row.student_id}`
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
                  <button
                    type="button"
                    onClick={() => {
                      setEditTarget({ path: entity, id: row.id })
                      form.reset({
                        entity,
                        name: row.name,
                        code: row.code,
                        level: row.level,
                        grade_id: row.grade_id,
                        section_id: row.section_id,
                        course_id: row.course_id,
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
                  {canDeleteEntity ? (
                    <button
                      type="button"
                      onClick={() => setDeleteTarget({ path: entity, id: row.id, label: row.name ?? row.code ?? row.id })}
                      className="button button-danger"
                      disabled={!canEditAcademic || destroy.isPending}
                    >
                      Eliminar
                    </button>
                  ) : (
                    <span className="action-note">No eliminable</span>
                  )}
                </span>
              ),
            },
          ]}
        />
        {entity === 'teaching-assignments' && assignments.length === 0 && <p className="empty-state">No hay cargas docentes registradas.</p>}
      </article>

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
