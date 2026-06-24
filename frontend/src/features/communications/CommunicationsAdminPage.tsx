import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Megaphone,
  Plus,
  Warning,
  SpinnerGap,
  Users,
  Eye,
  Trash,
  User
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { listAcademic, listAccounts } from '@/features/phase-one/api'
import { getApiError } from '@/lib/api/client'
import { listAnnouncements, createAnnouncement, archiveAnnouncement } from './api'
import type { CreateAnnouncementInput } from './types'

export function CommunicationsAdminPage() {
  const { user } = useAuth()
  const queryClient = useQueryClient()

  // Permissions check
  const isAuthorized = user?.roles.some((r) => ['superadmin', 'toe', 'coordinador_academico'].includes(r))

  // Tab: 'publish' | 'list'
  const [activeTab, setActiveTab] = useState<'publish' | 'list'>('publish')

  // Form states
  const [title, setTitle] = useState('')
  const [body, setBody] = useState('')
  const [publishAt, setPublishAt] = useState('')
  const [audienceType, setAudienceType] = useState<'all' | 'roles' | 'sections' | 'accounts'>('all')
  const [selectedRoles, setSelectedRoles] = useState<string[]>([])
  const [selectedSections, setSelectedSections] = useState<string[]>([])
  const [selectedAccounts, setSelectedAccounts] = useState<string[]>([])

  const [formError, setFormError] = useState('')
  const [successMsg, setSuccessMsg] = useState('')

  // Query metadata
  const accountsQuery = useQuery({
    queryKey: ['accounts'],
    queryFn: () => listAccounts(),
    enabled: !!isAuthorized
  })

  const sectionsQuery = useQuery({
    queryKey: ['academic', 'sections'],
    queryFn: () => listAcademic('sections'),
    enabled: !!isAuthorized
  })

  const gradesQuery = useQuery({
    queryKey: ['academic', 'grades'],
    queryFn: () => listAcademic('grades'),
    enabled: !!isAuthorized
  })

  const enrollmentsQuery = useQuery({
    queryKey: ['academic', 'enrollments'],
    queryFn: () => listAcademic('enrollments'),
    enabled: !!isAuthorized
  })

  // Announcements query
  const announcementsQuery = useQuery({
    queryKey: ['admin-announcements'],
    queryFn: () => listAnnouncements({ is_archived: false }),
    enabled: !!isAuthorized
  })

  const accounts = accountsQuery.data?.data || []
  const sections = sectionsQuery.data?.data || []
  const grades = gradesQuery.data?.data || []
  const enrollments = enrollmentsQuery.data?.data || []
  const announcements = announcementsQuery.data?.data || []

  // Resolve section name with grade
  const getSectionName = (secId: string) => {
    const sec = sections.find((s) => s.id === secId)
    const grade = grades.find((g) => g.id === sec?.grade_id)
    return sec ? `${grade?.name || ''} "${sec.name}"` : 'Sección'
  }

  // Previsualizar Destinatarios (Expected reach list & count)
  const getExpectedReach = () => {
    if (audienceType === 'all') {
      return accounts.filter((acc) => acc.active)
    }
    if (audienceType === 'roles') {
      return accounts.filter((acc) => acc.active && acc.roles.some((r) => selectedRoles.includes(r)))
    }
    if (audienceType === 'sections') {
      // Find students in the selected sections
      const enrolledStudentIds = enrollments
        .filter((e) => e.section_id && selectedSections.includes(e.section_id))
        .map((e) => e.student_id)
        .filter(Boolean) as string[]

      const matchedStudents = accounts.filter((acc) => enrolledStudentIds.includes(acc.id))

      return matchedStudents
    }
    if (audienceType === 'accounts') {
      return accounts.filter((acc) => selectedAccounts.includes(acc.id))
    }
    return []
  }

  const expectedReach = getExpectedReach()

  // Mutations
  const createAnnouncementMutation = useMutation({
    mutationFn: async (input: CreateAnnouncementInput) => createAnnouncement(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-announcements'] })
      setSuccessMsg('Comunicado publicado exitosamente.')
      // Reset form
      setTitle('')
      setBody('')
      setPublishAt('')
      setAudienceType('all')
      setSelectedRoles([])
      setSelectedSections([])
      setSelectedAccounts([])
      setFormError('')
      // Switch tab to list
      setTimeout(() => {
        setActiveTab('list')
        setSuccessMsg('')
      }, 1500)
    },
    onError: (err: unknown) => {
      setFormError(getApiError(err).message)
    }
  })

  const archiveMutation = useMutation({
    mutationFn: async (id: string) => archiveAnnouncement(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-announcements'] })
    }
  })

  if (!isAuthorized) {
    return (
      <div className="flex flex-col items-center justify-center p-12 text-center bg-red-50 border border-red-100 rounded-3xl text-red-800">
        <Warning size={36} className="text-red-500" />
        <h3 className="text-base font-bold mt-2">Acceso Denegado</h3>
        <p className="text-xs text-red-650 mt-1">No cuenta con permisos de publicación de comunicados.</p>
      </div>
    )
  }

  const handlePublish = (e: React.FormEvent) => {
    e.preventDefault()
    setFormError('')
    setSuccessMsg('')

    if (!title.trim()) {
      setFormError('El título del comunicado es obligatorio.')
      return
    }

    if (!body.trim()) {
      setFormError('El contenido (cuerpo) del comunicado es obligatorio.')
      return
    }

    let audience_ids: string[] | undefined = undefined
    if (audienceType === 'roles') {
      if (selectedRoles.length === 0) {
        setFormError('Debe seleccionar al menos un rol de destino.')
        return
      }
      audience_ids = selectedRoles
    } else if (audienceType === 'sections') {
      if (selectedSections.length === 0) {
        setFormError('Debe seleccionar al menos una sección de destino.')
        return
      }
      audience_ids = selectedSections
    } else if (audienceType === 'accounts') {
      if (selectedAccounts.length === 0) {
        setFormError('Debe seleccionar al menos un usuario de destino.')
        return
      }
      audience_ids = selectedAccounts
    }

    createAnnouncementMutation.mutate({
      title,
      body,
      audience_type: audienceType,
      audience_ids,
      publish_at: publishAt ? new Date(publishAt).toISOString() : undefined
    })
  }

  const handleRoleToggle = (role: string) => {
    setSelectedRoles((prev) =>
      prev.includes(role) ? prev.filter((r) => r !== role) : [...prev, role]
    )
  }

  const handleSectionToggle = (secId: string) => {
    setSelectedSections((prev) =>
      prev.includes(secId) ? prev.filter((s) => s !== secId) : [...prev, secId]
    )
  }

  const handleAccountToggle = (accId: string) => {
    setSelectedAccounts((prev) =>
      prev.includes(accId) ? prev.filter((id) => id !== accId) : [...prev, accId]
    )
  }

  const isPublishing = createAnnouncementMutation.isPending

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Header and top branding */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Megaphone className="text-blue-600 animate-pulse" size={24} weight="duotone" />
            Comunicaciones y Boletines
          </h2>
          <p className="text-xs text-slate-500">
            Publique anuncios escolares dirigidos y controle la bandeja de distribución general.
          </p>
        </div>

        <div className="flex bg-slate-150/80 p-1 rounded-xl w-fit border border-slate-200/50">
          <button
            onClick={() => setActiveTab('publish')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'publish'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-900'
            }`}
          >
            Publicar
          </button>
          <button
            onClick={() => setActiveTab('list')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'list'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-900'
            }`}
          >
            Historial
          </button>
        </div>
      </div>

      {activeTab === 'publish' ? (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto items-start">
          {/* Create Announcement Form */}
          <form
            onSubmit={handlePublish}
            className="lg:col-span-2 bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4"
          >
            <h3 className="text-sm font-extrabold text-slate-900 border-b border-slate-50 pb-2">
              Crear Nuevo Comunicado
            </h3>

            {successMsg && (
              <div className="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-xs font-semibold">
                {successMsg}
              </div>
            )}

            {formError && (
              <div className="p-3 bg-red-50 border border-red-100 rounded-xl text-red-800 text-xs font-semibold flex items-start gap-2">
                <Warning size={16} className="text-red-500 mt-0.5" />
                <span>{formError}</span>
              </div>
            )}

            <div className="space-y-3">
              <div>
                <label htmlFor="announcement-title" className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Título del Comunicado *</label>
                <input
                  id="announcement-title"
                  type="text"
                  placeholder="Ej. Suspensión de clases por mantenimiento"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  maxLength={200}
                />
              </div>

              <div>
                <label htmlFor="announcement-body" className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Contenido / Cuerpo *</label>
                <textarea
                  id="announcement-body"
                  rows={6}
                  placeholder="Escriba los detalles del comunicado..."
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  maxLength={10000}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="announcement-publish-at" className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Fecha / Hora de Publicación (Opcional)</label>
                  <input
                    id="announcement-publish-at"
                    type="datetime-local"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value={publishAt}
                    onChange={(e) => setPublishAt(e.target.value)}
                  />
                </div>

                <div>
                  <label htmlFor="announcement-audience-type" className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Segmentar Audiencia *</label>
                  <select
                    id="announcement-audience-type"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    value={audienceType}
                    onChange={(e) => {
                      setAudienceType(e.target.value as 'all' | 'roles' | 'sections' | 'accounts')
                      setSelectedRoles([])
                      setSelectedSections([])
                      setSelectedAccounts([])
                    }}
                  >
                    <option value="all">Todo el Colegio</option>
                    <option value="roles">Por Roles específicos</option>
                    <option value="sections">Por Grados y Secciones</option>
                    <option value="accounts">Por Usuarios específicos</option>
                  </select>
                </div>
              </div>

              {/* Audience Configurations details selector */}
              {audienceType === 'roles' && (
                <div className="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                  <label className="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Seleccionar Roles de Destinatarios</label>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {['gestor_usuarios', 'toe', 'psicologia', 'auxiliar', 'coordinador_academico', 'docente', 'padre', 'alumno', 'administrativo'].map((role) => (
                      <label key={role} className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                        <input
                          type="checkbox"
                          className="rounded text-blue-600 focus:ring-blue-500"
                          checked={selectedRoles.includes(role)}
                          onChange={() => handleRoleToggle(role)}
                        />
                        <span>{role}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}

              {audienceType === 'sections' && (
                <div className="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                  <label className="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Seleccionar Secciones</label>
                  {sections.length === 0 ? (
                    <p className="text-[11px] text-slate-400 italic">No hay secciones cargadas</p>
                  ) : (
                    <div className="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                      {sections.map((sec) => (
                        <label key={sec.id} className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                          <input
                            type="checkbox"
                            className="rounded text-blue-600 focus:ring-blue-500"
                            checked={selectedSections.includes(sec.id)}
                            onChange={() => handleSectionToggle(sec.id)}
                          />
                          <span>{getSectionName(sec.id)}</span>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {audienceType === 'accounts' && (
                <div className="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                  <label className="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Seleccionar Usuarios específicos</label>
                  {accounts.length === 0 ? (
                    <p className="text-[11px] text-slate-400 italic">Cargando cuentas...</p>
                  ) : (
                    <div className="grid grid-cols-1 gap-2 max-h-40 overflow-y-auto">
                      {accounts.map((acc) => (
                        <label key={acc.id} className="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                          <input
                            type="checkbox"
                            className="rounded text-blue-600 focus:ring-blue-500"
                            checked={selectedAccounts.includes(acc.id)}
                            onChange={() => handleAccountToggle(acc.id)}
                          />
                          <span>{acc.name} ({acc.roles.join(', ')})</span>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>

            <button
              type="submit"
              disabled={isPublishing}
              className="w-full py-2.5 rounded-xl text-white font-extrabold bg-blue-600 hover:bg-blue-700 text-xs shadow-md shadow-blue-500/10 active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              {isPublishing ? (
                <>
                  <SpinnerGap className="spin" size={16} />
                  Publicando...
                </>
              ) : (
                <>
                  <Plus size={16} weight="bold" />
                  Publicar Comunicado
                </>
              )}
            </button>
          </form>

          {/* Recipient Preview panel */}
          <div className="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
            <h3 className="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
              <Eye size={16} weight="bold" className="text-blue-500" />
              Destinatarios Estimados (Alcance)
            </h3>

            <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 flex items-center justify-between">
              <div>
                <span className="text-[10px] text-slate-450 font-bold uppercase tracking-wider block">Total alcance</span>
                <span className="text-xl font-black text-blue-700">{expectedReach.length} usuarios</span>
              </div>
              <Users size={28} className="text-blue-500" weight="duotone" />
            </div>

            <div className="space-y-2">
              <span className="text-[10px] text-slate-450 font-bold uppercase tracking-wider block">Listado preliminar</span>
              {expectedReach.length === 0 ? (
                <p className="text-xs text-slate-455 italic text-center py-6">Seleccione criterios para previsualizar alcance.</p>
              ) : (
                <div className="max-h-64 overflow-y-auto space-y-1.5">
                  {expectedReach.map((acc) => (
                    <div key={acc.id} className="flex items-center justify-between p-2 bg-slate-50 rounded-xl text-[11px] font-semibold border border-slate-100/50">
                      <div className="flex items-center gap-1.5">
                        <User size={12} className="text-slate-400" />
                        <span className="text-slate-800 line-clamp-1">{acc.name}</span>
                      </div>
                      <span className="text-[9px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-black uppercase">
                        {acc.roles[0] || 'usuario'}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      ) : (
        /* Historical List View */
        <div className="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4 max-w-6xl mx-auto">
          <h3 className="text-sm font-extrabold text-slate-900 border-b border-slate-50 pb-2">
            Comunicados Publicados Activos
          </h3>

          {announcements.length === 0 ? (
            <div className="flex flex-col items-center justify-center p-16 text-center">
              <Megaphone size={32} className="text-slate-300 mb-2" />
              <p className="text-xs font-bold text-slate-500">No hay comunicados publicados activos.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs font-bold border-collapse">
                <thead>
                  <tr className="border-b border-slate-100 text-[10px] text-slate-450 uppercase tracking-wider bg-slate-50 rounded-xl">
                    <th className="p-3">Título</th>
                    <th className="p-3">Destinatario</th>
                    <th className="p-3">F. Publicación</th>
                    <th className="p-3 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {announcements.map((item) => (
                    <tr key={item.id} className="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                      <td className="p-3 font-extrabold text-slate-900">{item.title}</td>
                      <td className="p-3 text-slate-500 font-semibold uppercase text-[10px]">
                        {item.audience_type}{item.audience_ids?.length ? ` (${item.audience_ids.length})` : ''}
                      </td>
                      <td className="p-3 text-slate-550 text-[11px] font-semibold">
                        {item.publish_at ? new Date(item.publish_at).toLocaleString('es-PE') : new Date(item.created_at).toLocaleString('es-PE')}
                      </td>
                      <td className="p-3 text-center">
                        <button
                          onClick={() => {
                            if (window.confirm('¿Está seguro de que desea archivar este comunicado?')) {
                              archiveMutation.mutate(item.id)
                            }
                          }}
                          className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors inline-flex items-center justify-center cursor-pointer"
                          title="Archivar Comunicado"
                        >
                          <Trash size={16} />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </section>
  )
}
