import { useState, useEffect, useRef } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Megaphone,
  Envelope,
  EnvelopeOpen,
  SpinnerGap,
  User,
  Clock,
  ArrowLeft,
  Bell
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { getStudentSummary, listLinkedStudents, listAcademic } from '@/features/phase-one/api'
import { listAnnouncements, listNotifications, markAnnouncementRead } from './api'
import type { Announcement } from './types'

const contextKey = 'cienciasnet:selected-student-id'

export function CommunicationsPortalPage() {
  const { user } = useAuth()
  const queryClient = useQueryClient()

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

  // Fetch metadata to resolve child's section
  const summaryQuery = useQuery({
    queryKey: ['student-summary', selectedId],
    queryFn: () => getStudentSummary(selectedId),
    enabled: Boolean(selectedId)
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

  // Active bulletins query
  const announcementsQuery = useQuery({
    queryKey: ['portal-announcements'],
    queryFn: () => listAnnouncements({ is_archived: false })
  })

  // Personal alerts query
  const notificationsQuery = useQuery({
    queryKey: ['portal-notifications'],
    queryFn: () => listNotifications()
  })

  // Selected announcement for reading details
  const [reading, setReading] = useState<Announcement | null>(null)
  const [activeTab, setActiveTab] = useState<'bulletins' | 'alerts'>('bulletins')

  const availableSections = sectionsQuery.data?.data || []
  const availableGrades = gradesQuery.data?.data || []
  const rawAnnouncements = announcementsQuery.data?.data || []
  const alerts = notificationsQuery.data?.data || []

  // Resolve section name and ID for isolation
  const getGradeName = (gradeId?: string) => {
    return availableGrades.find((g) => g.id === gradeId)?.name || ''
  }

  const enrolledSections = availableSections.filter((sec) => {
    return summaryQuery.data?.enrollments.some(
      (e) => e.section === sec.name && e.grade === getGradeName(sec.grade_id)
    )
  })

  const activeSectionId = enrolledSections[0]?.id || ''

  // Mark as read mutation
  const markReadMutation = useMutation({
    mutationFn: async (id: string) => markAnnouncementRead(id),
    onSuccess: () => {
      // Refresh count immediately
      queryClient.invalidateQueries({ queryKey: ['portal-announcements'] })
    }
  })

  // Track read request IDs to prevent duplicate calls (idempotence)
  const markedReadIds = useRef(new Set<string>())

  // Idempotent automatic mark as read
  useEffect(() => {
    if (reading && !reading.is_read && !markedReadIds.current.has(reading.id)) {
      markedReadIds.current.add(reading.id)
      markReadMutation.mutate(reading.id)
    }
  }, [reading, markReadMutation])

  // Filter announcements matching target audience
  const filterAnnouncements = () => {
    return rawAnnouncements.filter((ann) => {
      // 1. All
      if (ann.audience_type === 'all') return true

      // 2. Roles
      if (ann.audience_type === 'roles') {
        const targetRoles = ann.audience_ids || []
        // Parent matches if target has 'padre' or child has 'alumno'
        const matchesUser = user?.roles.some((r) => targetRoles.includes(r))
        const matchesChild = isParent && targetRoles.includes('alumno')
        return matchesUser || matchesChild
      }

      // 3. Sections
      if (ann.audience_type === 'sections') {
        const targetSections = ann.audience_ids || []
        return activeSectionId && targetSections.includes(activeSectionId)
      }

      // 4. Accounts
      if (ann.audience_type === 'accounts') {
        const targetAccounts = ann.audience_ids || []
        return targetAccounts.includes(user?.id || '') || (selectedId && targetAccounts.includes(selectedId))
      }

      return false
    })
  }

  const announcements = filterAnnouncements()

  const isLoading =
    announcementsQuery.isLoading ||
    notificationsQuery.isLoading ||
    (isParent && (studentsQuery.isLoading || summaryQuery.isLoading))

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Selector and Header Panel */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <Megaphone className="text-blue-600" size={24} weight="duotone" />
            Comunicaciones
          </h2>
          <p className="text-xs text-slate-500">
            Revise los boletines oficiales y avisos emitidos por la dirección escolar.
          </p>
        </div>

        {isParent && (studentsQuery.data?.length || 0) > 1 && (
          <div className="w-full md:w-64">
            <label htmlFor="child-select" className="text-[10px] font-extrabold text-slate-400 block mb-1 uppercase tracking-wider">Hijo / Alumno</label>
            <div className="relative">
              <select
                id="child-select"
                className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none pr-8"
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
          onClick={() => {
            setActiveTab('bulletins')
            setReading(null)
          }}
          className={`pb-3 text-sm font-bold transition-all border-b-2 flex items-center gap-2 cursor-pointer ${
            activeTab === 'bulletins' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600'
          }`}
        >
          <Megaphone size={16} />
          Comunicados y Boletines
        </button>
        <button
          onClick={() => {
            setActiveTab('alerts')
            setReading(null)
          }}
          className={`pb-3 text-sm font-bold transition-all border-b-2 flex items-center gap-2 cursor-pointer ${
            activeTab === 'alerts' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600'
          }`}
        >
          <Bell size={16} />
          Notificaciones Personales
        </button>
      </div>

      {isLoading ? (
        <div className="flex flex-col items-center justify-center py-16">
          <SpinnerGap className="spin text-blue-500" size={36} />
          <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando bandeja de entrada...</p>
        </div>
      ) : activeTab === 'bulletins' ? (
        /* Bulletins / Announcements Tab */
        reading ? (
          /* Bulletin detail reader view */
          <div className="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm max-w-3xl mx-auto space-y-6">
            <button
              onClick={() => setReading(null)}
              className="flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors cursor-pointer"
            >
              <ArrowLeft size={14} weight="bold" />
              Volver a la bandeja
            </button>

            <div className="space-y-4">
              <div className="space-y-2">
                <h3 className="text-lg font-black text-slate-900 leading-tight">
                  {reading.title}
                </h3>
                <div className="flex items-center gap-4 text-[10px] text-slate-400 font-extrabold uppercase tracking-wide">
                  <span className="flex items-center gap-1">
                    <Clock size={12} />
                    {reading.publish_at ? new Date(reading.publish_at).toLocaleDateString('es-PE') : new Date(reading.created_at).toLocaleDateString('es-PE')}
                  </span>
                  <span className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded">
                    {reading.audience_type}
                  </span>
                </div>
              </div>

              <div className="p-4 bg-slate-50/50 border border-slate-100 rounded-2xl text-xs font-semibold text-slate-700 leading-relaxed whitespace-pre-wrap">
                {reading.body}
              </div>
            </div>
          </div>
        ) : (
          /* Bulletin list inbox bandeja */
          <div className="space-y-4 max-w-4xl mx-auto">
            {announcements.length === 0 ? (
              <div className="flex flex-col items-center justify-center p-16 text-center bg-white border border-slate-100 rounded-3xl">
                <EnvelopeOpen size={32} className="text-slate-300 mb-2" />
                <p className="text-xs font-bold text-slate-500">No tiene comunicados pendientes.</p>
                <p className="text-[11px] text-slate-400 mt-1">Le notificaremos cuando la dirección publique anuncios nuevos.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-3">
                {announcements.map((ann) => (
                  <button
                    key={ann.id}
                    onClick={() => setReading(ann)}
                    className="w-full text-left p-4 bg-white hover:bg-slate-50/50 border border-slate-100/80 rounded-2xl shadow-sm flex items-center justify-between gap-4 transition-all hover:scale-[1.005] group cursor-pointer"
                  >
                    <div className="flex items-start gap-3 flex-1 min-w-0">
                      <div className="mt-1">
                        {ann.is_read ? (
                          <EnvelopeOpen size={18} className="text-slate-450" />
                        ) : (
                          <div className="relative">
                            <Envelope size={18} className="text-blue-600" />
                            <span className="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-blue-600 animate-ping" />
                            <span className="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-blue-600" />
                          </div>
                        )}
                      </div>
                      <div className="space-y-1 min-w-0">
                        <h4 className={`text-xs leading-snug line-clamp-1 ${ann.is_read ? 'text-slate-600 font-semibold' : 'text-slate-900 font-black'}`}>
                          {ann.title}
                        </h4>
                        <p className="text-[11px] text-slate-500 line-clamp-1 leading-normal font-semibold">
                          {ann.body}
                        </p>
                      </div>
                    </div>

                    <div className="text-[9px] text-slate-400 font-extrabold uppercase tracking-wider flex flex-col items-end gap-1.5">
                      <span>
                        {ann.publish_at ? new Date(ann.publish_at).toLocaleDateString('es-PE') : new Date(ann.created_at).toLocaleDateString('es-PE')}
                      </span>
                      {!ann.is_read && (
                        <span className="bg-blue-50 text-blue-600 px-2 py-0.5 rounded font-black text-[8px]">
                          Nuevo
                        </span>
                      )}
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>
        )
      ) : (
        /* Alerts Tab (Personal Notifications log) */
        <div className="space-y-3 max-w-3xl mx-auto">
          {alerts.length === 0 ? (
            <div className="flex flex-col items-center justify-center p-16 text-center bg-white border border-slate-100 rounded-3xl">
              <Bell size={32} className="text-slate-300 mb-2" />
              <p className="text-xs font-bold text-slate-500">No tiene notificaciones de alerta personales.</p>
            </div>
          ) : (
            <div className="space-y-2.5">
              {alerts.map((alert) => (
                <div
                  key={alert.id}
                  className="p-3.5 bg-white border border-slate-100 rounded-2xl shadow-sm flex items-start gap-3 transition-colors"
                >
                  <Bell size={16} className="text-blue-500 mt-0.5" />
                  <div className="space-y-1">
                    <h4 className="text-xs font-black text-slate-950 leading-tight">
                      {alert.title}
                    </h4>
                    <p className="text-[11px] text-slate-500 leading-normal font-semibold">
                      {alert.body}
                    </p>
                    <span className="text-[9px] text-slate-400 font-extrabold flex items-center gap-1">
                      <Clock size={10} />
                      {new Date(alert.created_at).toLocaleString('es-PE')}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </section>
  )
}
