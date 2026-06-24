import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, useEffect } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { DataTable } from '@/components/shared/DataTable'
import { getApiError } from '@/lib/api/client'
import { createFamilyLink, listFamilyLinks, removeFamilyLink, searchDni } from './api'

const schema = z.object({
  parent_account_id: z.string().uuid(),
  student_id: z.string().uuid(),
  relationship: z.enum(['padre', 'madre', 'apoderado']),
})
type Form = z.infer<typeof schema>

export function FamilyAdminPage() {
  const client = useQueryClient()
  const links = useQuery({ queryKey: ['family-links'], queryFn: listFamilyLinks })
  const form = useForm<Form>({ resolver: zodResolver(schema), defaultValues: { parent_account_id: '', student_id: '', relationship: 'padre' } })
  const invalidate = async () => client.invalidateQueries({ queryKey: ['family-links'] })
  const create = useMutation({ mutationFn: createFamilyLink, onSuccess: async () => { form.reset(); setParentDni(''); setStudentDni(''); setParentName(''); setStudentName(''); await invalidate() } })
  const remove = useMutation({ mutationFn: removeFamilyLink, onSuccess: invalidate })

  const [parentDni, setParentDni] = useState('')
  const [studentDni, setStudentDni] = useState('')
  const [parentName, setParentName] = useState('')
  const [studentName, setStudentName] = useState('')

  useEffect(() => {
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
    } else {
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

  const parentAccountId = useWatch({ control: form.control, name: 'parent_account_id' })
  const studentId = useWatch({ control: form.control, name: 'student_id' })
  const isValidForm = !!parentAccountId && !!studentId

  return (
    <section className="page-stack">
      <header><p className="eyebrow">Familias</p><h1>Vínculos familiares</h1><p>Relaciona cuentas de padres con uno o varios alumnos. No existe autorregistro.</p></header>
      <form className="panel form-grid" onSubmit={form.handleSubmit((values) => create.mutate(values))}>
        <h2>Nuevo vínculo</h2>
        <label>
          DNI del Familiar
          <input value={parentDni} onChange={e => setParentDni(e.target.value)} maxLength={15} />
          {parentName && <small style={{ color: parentName === 'No encontrado' ? 'red' : 'green' }}>{parentName}</small>}
        </label>
        <label>
          DNI del Alumno
          <input value={studentDni} onChange={e => setStudentDni(e.target.value)} maxLength={15} />
          {studentName && <small style={{ color: studentName === 'No encontrado' ? 'red' : 'green' }}>{studentName}</small>}
        </label>
        <label>Relación<select {...form.register('relationship')}><option>padre</option><option>madre</option><option>apoderado</option></select></label>
        <button className="button button-primary" disabled={create.isPending || !isValidForm}>Vincular</button>
        {create.error && <p className="form-error">{getApiError(create.error).message}</p>}
      </form>
      <div className="panel">
        <DataTable rows={links.data?.data} isLoading={links.isLoading} error={links.error} columns={[
          { label: 'Alumno', render: (link) => link.student_name },
          { label: 'Familiar', render: (link) => link.parent_name },
          { label: 'Relación', render: (link) => link.relationship },
          { label: 'Acción', render: (link) => <button className="button button-secondary" onClick={() => confirm('¿Retirar este vínculo? La acción quedará auditada.') && remove.mutate(link.id)}>Desvincular</button> },
        ]} />
      </div>
    </section>
  )
}
