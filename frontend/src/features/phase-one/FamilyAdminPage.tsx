import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, useEffect } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { getApiError } from '@/lib/api/client'
import { createFamilyLink, listAcademic, listAccounts, listFamilyLinks, removeFamilyLink, searchDni } from './api'

const schema = z.object({
  parent_account_id: z.string().uuid(),
  student_id: z.string().uuid(),
  relationship: z.enum(['padre', 'madre', 'apoderado']),
})
type Form = z.infer<typeof schema>

export function FamilyAdminPage() {
  const client = useQueryClient()
  const links = useQuery({ queryKey: ['family-links'], queryFn: listFamilyLinks })
  const grades = useQuery({ queryKey: ['academic', 'grades'], queryFn: () => listAcademic('grades') })
  const sections = useQuery({ queryKey: ['academic', 'sections'], queryFn: () => listAcademic('sections') })
  const enrollments = useQuery({ queryKey: ['academic', 'enrollments'], queryFn: () => listAcademic('enrollments') })
  const accounts = useQuery({ queryKey: ['accounts', 'families'], queryFn: () => listAccounts('', 'superadmin') })
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { parent_account_id: '', student_id: '', relationship: 'padre' } })
  const selectedParentId = useWatch({ control: form.control, name: 'parent_account_id' })
  const selectedStudentId = useWatch({ control: form.control, name: 'student_id' })
  const invalidate = async () => client.invalidateQueries({ queryKey: ['family-links'] })
  const create = useMutation({ mutationFn: createFamilyLink, onSuccess: async () => { form.reset(); setParentDni(''); setStudentDni(''); setParentName(''); setStudentName(''); await invalidate() } })
  const remove = useMutation({ mutationFn: removeFamilyLink, onSuccess: invalidate })

  const [parentDni, setParentDni] = useState('')
  const [studentDni, setStudentDni] = useState('')
  const [parentName, setParentName] = useState('')
  const [studentName, setStudentName] = useState('')
  const [gradeFilter, setGradeFilter] = useState('')
  const [sectionFilter, setSectionFilter] = useState('')
  const filteredSections = sections.data?.data.filter((section) => !gradeFilter || section.grade_id === gradeFilter) ?? []
  const parentOptions = accounts.data?.data.filter((account) => account.roles.includes('padre')) ?? []
  const studentSectionById = new Map(
    (enrollments.data?.data ?? [])
      .filter((enrollment) => enrollment.student_id && enrollment.section_id)
      .map((enrollment) => [enrollment.student_id!, enrollment.section_id!])
  )
  const selectedSectionIds = sectionFilter
    ? [sectionFilter]
    : filteredSections.map((section) => section.id)
  const filteredLinks = links.data?.data.filter((link) => {
    const matchesSearch = !studentDni || link.student_name.toLowerCase().includes(studentDni.toLowerCase()) || link.parent_name.toLowerCase().includes(studentDni.toLowerCase())
    const linkSectionId = studentSectionById.get(link.student_id)
    const matchesSection = !gradeFilter || (linkSectionId ? selectedSectionIds.includes(linkSectionId) : false)
    return matchesSearch && matchesSection
  })

  useEffect(() => {
    if (!parentDni) return
    if (parentDni.length >= 8) {
      searchDni('parents', parentDni).then(res => {
        if (res) {
          setParentName(res.name)
          form.setValue('parent_account_id', res.user_id)
        } else {
          setParentName('No encontrado')
          form.setValue('parent_account_id', '')
        }
      })
    } else if (parentDni.length > 0) {
      setParentName('')
      form.setValue('parent_account_id', '')
    }
  }, [parentDni, form])

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

  const isValidForm = !!selectedParentId && !!selectedStudentId

  return (
    <section className="page-stack">
      <header><p className="eyebrow">Familias</p><h1>Vínculos familiares</h1><p>Relaciona cuentas de padres con uno o varios alumnos. No existe autorregistro.</p></header>
      <form className="panel form-grid" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
        <h2>Nuevo vínculo</h2>
        <label>
          DNI del familiar
          <input value={parentDni} onChange={e => setParentDni(e.target.value)} maxLength={15} />
          {parentName && <small style={{ color: parentName === 'No encontrado' ? 'red' : 'green' }}>{parentName}</small>}
        </label>
        <label>Familiar registrado
          <select value={selectedParentId} onChange={(event) => {
            const parent = parentOptions.find((item) => item.id === event.target.value)
            form.setValue('parent_account_id', event.target.value)
            setParentName(parent?.name ?? '')
            setParentDni('')
          }}>
            <option value="">Buscar por DNI o elegir cuenta</option>
            {parentOptions.map((parent) => <option value={parent.id} key={parent.id}>{parent.name} - {parent.email}</option>)}
          </select>
        </label>
        <label>
          Alumno por DNI, nombre o apellido
          <input value={studentDni} onChange={e => setStudentDni(e.target.value)} list="family-student-options" maxLength={40} />
          <datalist id="family-student-options">
            {links.data?.data.map((link) => <option key={link.student_id} value={link.student_name}>{link.student_name}</option>)}
          </datalist>
          {studentName && <small style={{ color: studentName === 'No encontrado' ? 'red' : 'green' }}>{studentName}</small>}
        </label>
        <label>Relación<select {...form.register('relationship')}><option>padre</option><option>madre</option><option>apoderado</option></select></label>
        <label>Grado<select value={gradeFilter} onChange={(event) => { setGradeFilter(event.target.value); setSectionFilter('') }}><option value="">Todos</option>{grades.data?.data.map((grade) => <option value={grade.id} key={grade.id}>{grade.name}</option>)}</select></label>
        <label>Seccion<select value={sectionFilter} onChange={(event) => setSectionFilter(event.target.value)} disabled={!gradeFilter}><option value="">Todas</option>{filteredSections.map((section) => <option value={section.id} key={section.id}>{section.name}</option>)}</select></label>
        <button className="button button-primary" disabled={create.isPending || !isValidForm}>Vincular</button>
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>
      <div className="panel">
        <DataTable rows={filteredLinks} isLoading={links.isLoading} error={links.error} columns={[
          { label: 'Alumno', render: (link) => link.student_name },
          { label: 'Familiar', render: (link) => link.parent_name },
          { label: 'Grado / seccion', render: (link) => {
            const sectionId = studentSectionById.get(link.student_id)
            const section = sections.data?.data.find((item) => item.id === sectionId)
            const grade = grades.data?.data.find((item) => item.id === section?.grade_id)
            return section ? `${grade?.name ?? 'Grado'} ${section.name}` : 'Sin matricula activa'
          } },
          { label: 'Relación', render: (link) => link.relationship },
          { label: 'Acción', render: (link) => <button className="button button-secondary" onClick={() => confirm('¿Retirar este vínculo? La acción quedará auditada.') && remove.mutate(link.id)}>Desvincular</button> },
        ]} />
      </div>
    </section>
  )
}
