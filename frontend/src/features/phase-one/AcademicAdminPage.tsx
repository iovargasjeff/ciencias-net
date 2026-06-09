import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueries, useQueryClient } from '@tanstack/react-query'
import { useState, useEffect } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { getApiError } from '@/lib/api/client'
import { createAcademic, listAcademic, deleteAcademic, searchDni, type AcademicPath } from './api'

const entities: Array<{ path: AcademicPath; label: string }> = [
  { path: 'academic-periods', label: 'Periodos' }, { path: 'grades', label: 'Grados' },
  { path: 'sections', label: 'Secciones' }, { path: 'courses', label: 'Cursos' },
  { path: 'enrollments', label: 'Matrículas' }, { path: 'teaching-assignments', label: 'Cargas docentes' },
]
const schema = z.object({
  entity: z.enum(['academic-periods', 'grades', 'sections', 'courses', 'enrollments', 'teaching-assignments']),
  name: z.string().optional(), code: z.string().optional(), start_date: z.string().optional(), end_date: z.string().optional(),
  level: z.string().optional(), order: z.string().optional(), capacity: z.string().optional(), academic_period_id: z.string().optional(),
  grade_id: z.string().optional(), student_id: z.string().optional(), section_id: z.string().optional(),
  teacher_id: z.string().optional(), course_id: z.string().optional(),
})
type Form = z.infer<typeof schema>

function payload(values: Form): Record<string, unknown> {
  const common: Record<string, unknown> = Object.fromEntries(Object.entries(values).filter(([key, value]) => key !== 'entity' && value))
  if (values.order) common.order = Number(values.order)
  if (values.capacity) common.capacity = Number(values.capacity)
  if (values.entity === 'academic-periods') common.status = 'draft'
  if (values.entity === 'enrollments') common.enrolled_at = new Date().toISOString().slice(0, 10)
  return common
}

export function AcademicAdminPage() {
  const client = useQueryClient()
  const queries = useQueries({ queries: entities.map(({ path }) => ({ queryKey: ['academic', path], queryFn: () => listAcademic(path) })) })
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { entity: 'academic-periods' } })
  const entity = useWatch({ control: form.control, name: 'entity' })

  const [studentDni, setStudentDni] = useState('')
  const [studentName, setStudentName] = useState('')
  const [teacherDni, setTeacherDni] = useState('')
  const [teacherName, setTeacherName] = useState('')
  const [successMsg, setSuccessMsg] = useState('')

  useEffect(() => {
    form.reset({ entity })
    setStudentDni('')
    setStudentName('')
    setTeacherDni('')
    setTeacherName('')
    setSuccessMsg('')
  }, [entity, form])

  useEffect(() => {
    if (studentDni.length >= 8) {
      searchDni('students', studentDni).then(res => {
        if (res) {
          setStudentName(res.name)
          form.setValue('student_id', res.id)
        } else {
          setStudentName('No encontrado')
          form.setValue('student_id', '')
        }
      })
    } else {
      setStudentName('')
      form.setValue('student_id', '')
    }
  }, [studentDni, form])

  useEffect(() => {
    if (teacherDni.length >= 8) {
      searchDni('teachers', teacherDni).then(res => {
        if (res) {
          setTeacherName(res.name)
          form.setValue('teacher_id', res.id)
        } else {
          setTeacherName('No encontrado')
          form.setValue('teacher_id', '')
        }
      })
    } else {
      setTeacherName('')
      form.setValue('teacher_id', '')
    }
  }, [teacherDni, form])

  const create = useMutation({
    mutationFn: (values: Form) => createAcademic(values.entity, payload(values)),
    onSuccess: async () => { 
      const currentEntity = form.getValues('entity')
      form.reset({ entity: currentEntity })
      setStudentDni('')
      setStudentName('')
      setTeacherDni('')
      setTeacherName('')
      setSuccessMsg('Registro creado exitosamente.')
      setTimeout(() => setSuccessMsg(''), 3000)
      await client.invalidateQueries({ queryKey: ['academic'] }) 
    },
  })
  const destroy = useMutation({
    mutationFn: ({ path, id }: { path: AcademicPath, id: string }) => deleteAcademic(path, id),
    onSuccess: async () => { await client.invalidateQueries({ queryKey: ['academic'] }) },
  })

  return (
    <section className="page-stack">
      <header><p className="eyebrow">Estructura académica</p><h1>Periodos, grados y cargas</h1><p>Las cargas anteriores permanecen visibles cuando cambia el docente.</p></header>
      <form className="panel form-grid" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
        <h2>Crear estructura</h2>
        <label>Entidad<select {...form.register('entity')}>{entities.map((item) => <option value={item.path} key={item.path}>{item.label}</option>)}</select></label>
        
        {['academic-periods', 'grades', 'sections', 'courses'].includes(entity) && <label>Nombre<input {...form.register('name')} required /></label>}
        
        {entity === 'academic-periods' && <><label>Inicio<input type="date" {...form.register('start_date')} required /></label><label>Fin<input type="date" {...form.register('end_date')} required /></label></>}
        
        {entity === 'grades' && <>
          <label>Nivel<select {...form.register('level')}><option>inicial</option><option>primaria</option><option>secundaria</option></select></label>
          <label title="Número secuencial para organizar los grados (ej: 1 para Primer Grado)">Orden ℹ️<input type="number" min="1" {...form.register('order')} /></label>
          <label>Periodo<select {...form.register('academic_period_id')}>{queries[0].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
        </>}
        
        {entity === 'sections' && <>
          <label>Grado<select {...form.register('grade_id')}>{queries[1].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
          <label>Capacidad<input type="number" min="1" {...form.register('capacity')} required /></label>
        </>}
        
        {entity === 'courses' && <label>Código<input {...form.register('code')} required /></label>}
        
        {entity === 'enrollments' && <>
          <label>DNI Alumno
            <input value={studentDni} onChange={e => setStudentDni(e.target.value)} maxLength={15} required />
            {studentName && <small style={{ color: studentName === 'No encontrado' ? 'red' : 'green' }}>{studentName}</small>}
          </label>
          <label>Sección<select {...form.register('section_id')}>{queries[2].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
          <label>Periodo<select {...form.register('academic_period_id')}>{queries[0].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
        </>}
        
        {entity === 'teaching-assignments' && <>
          <label>DNI Docente
            <input value={teacherDni} onChange={e => setTeacherDni(e.target.value)} maxLength={15} required />
            {teacherName && <small style={{ color: teacherName === 'No encontrado' ? 'red' : 'green' }}>{teacherName}</small>}
          </label>
          <label>Curso<select {...form.register('course_id')}>{queries[3].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
          <label>Sección<select {...form.register('section_id')}>{queries[2].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
          <label>Periodo<select {...form.register('academic_period_id')}>{queries[0].data?.data.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
        </>}
        
        <button className="button button-primary" disabled={create.isPending || (entity === 'enrollments' && !form.watch('student_id')) || (entity === 'teaching-assignments' && !form.watch('teacher_id'))}>Crear</button>
        {successMsg && <p className="form-success text-success">{successMsg}</p>}
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>
      <div className="academic-grid">{entities.map((item, index) => <article className="panel" key={item.path}>
        <h2>{item.label}</h2>
        <DataTable rows={queries[index].data?.data} isLoading={queries[index].isLoading} error={queries[index].error} columns={[
          { label: 'Registro', render: (row) => row.name ?? row.code ?? row.id },
          { label: 'Estado / vigencia', render: (row) => row.status ?? (row.active === false ? `Histórica hasta ${row.valid_until}` : row.valid_from ?? 'Vigente') },
          { label: 'Acciones', render: (row) => <button type="button" onClick={() => { if(confirm('¿Eliminar registro?')) destroy.mutate({ path: item.path, id: row.id }) }} className="button text-red-500" disabled={destroy.isPending}>Eliminar</button> }
        ]} />
      </article>)}</div>
    </section>
  )
}
