import { useState, useRef } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  FileText,
  Plus,
  Trash,
  PencilSimple,
  ArrowSquareOut,
  CloudArrowUp,
  Warning,
  SpinnerGap,
  DownloadSimple
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { listAcademic, listAccounts } from '@/features/phase-one/api'
import { getApiError } from '@/lib/api/client'
import {
  listMaterials,
  createMaterial,
  createExternalMaterial,
  updateMaterial,
  archiveMaterial,
  replaceMaterialFile,
  downloadMaterial
} from './api'
import type { Material } from './types'

export function MaterialsAdminPage() {
  const { user } = useAuth()
  const queryClient = useQueryClient()

  // Roles check
  const isDocente = user?.roles.includes('docente') && !user?.roles.some((r) => ['superadmin', 'coordinador_academico'].includes(r))

  // Selected teaching load (assignment)
  const [selectedLoadId, setSelectedLoadId] = useState('')

  // Modal control states
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [showReplaceModal, setShowReplaceModal] = useState(false)
  const [showArchiveModal, setShowArchiveModal] = useState(false)

  // Selected material for edit/replace/archive
  const [activeMaterial, setActiveMaterial] = useState<Material | null>(null)

  // Creation form states
  const [createType, setCreateType] = useState<'file' | 'link'>('file')
  const [createTitle, setCreateTitle] = useState('')
  const [createDesc, setCreateDesc] = useState('')
  const [createWeek, setCreateWeek] = useState<string>('')
  const [createFile, setCreateFile] = useState<File | null>(null)
  const [createUrl, setCreateUrl] = useState('')
  const [createError, setCreateError] = useState('')
  const [uploadProgress, setUploadProgress] = useState(0)

  // Edit form states
  const [editTitle, setEditTitle] = useState('')
  const [editDesc, setEditDesc] = useState('')
  const [editWeek, setEditWeek] = useState<string>('')
  const [editError, setEditError] = useState('')

  // Replace file form states
  const [replaceFile, setReplaceFile] = useState<File | null>(null)
  const [replaceError, setReplaceError] = useState('')
  const [replaceProgress, setReplaceProgress] = useState(0)

  // File input refs
  const fileInputRef = useRef<HTMLInputElement>(null)
  const replaceInputRef = useRef<HTMLInputElement>(null)

  // 1. Fetch Academic Metadata
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

  // 2. Filter assignments based on active user role (Aislamiento de Carga)
  const rawAssignments = assignmentsQuery.data?.data || []
  const assignments = isDocente
    ? rawAssignments.filter((a) => a.teacher_id === user?.id)
    : rawAssignments

  // Helper mappings
  const getCourseName = (courseId?: string) => {
    const course = coursesQuery.data?.data.find((c) => c.id === courseId)
    return course?.name || `Curso (${courseId?.slice(0, 8)})`
  }

  const getSectionName = (sectionId?: string) => {
    const sec = sectionsQuery.data?.data.find((s) => s.id === sectionId)
    const grade = gradesQuery.data?.data.find((g) => g.id === sec?.grade_id)
    return sec ? `${grade?.name || ''} "${sec.name}"` : `Sección (${sectionId?.slice(0, 8)})`
  }

  const getTeacherName = (teacherId?: string) => {
    const teacher = accountsQuery.data?.data.find((a) => a.id === teacherId)
    return teacher?.name || `Docente (${teacherId?.slice(0, 8)})`
  }

  const selectedLoad = assignments.find((a) => a.id === selectedLoadId)

  // 3. Fetch materials for selected load
  const { data: materialsData, isLoading: isLoadingMaterials } = useQuery({
    queryKey: ['materials', selectedLoadId],
    queryFn: () => listMaterials({ teaching_assignment_id: selectedLoadId }),
    enabled: !!selectedLoadId
  })

  const materials = materialsData?.data || []
  // Sort materials: Week first, then Created Date
  const sortedMaterials = [...materials].sort((a, b) => {
    const wA = a.week ?? 999
    const wB = b.week ?? 999
    if (wA !== wB) return wA - wB
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })

  // Format file size
  const formatBytes = (bytes: number | null) => {
    if (bytes === null || bytes === undefined) return ''
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  // --- MUTATIONS ---

  // Create Material Mutation
  const createMaterialMutation = useMutation({
    mutationFn: async (vars: { type: 'file' | 'link' }) => {
      const parsedWeek = createWeek ? Number(createWeek) : undefined
      if (vars.type === 'file') {
        if (!createFile) throw new Error('El archivo es obligatorio')
        return createMaterial(
          {
            title: createTitle,
            description: createDesc || undefined,
            teaching_assignment_id: selectedLoadId,
            week: parsedWeek,
            file: createFile
          },
          (progressEvent) => {
            if (progressEvent.total) {
              const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
              setUploadProgress(percent)
            }
          }
        )
      } else {
        return createExternalMaterial({
          title: createTitle,
          description: createDesc || undefined,
          teaching_assignment_id: selectedLoadId,
          week: parsedWeek,
          url: createUrl
        })
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['materials', selectedLoadId] })
      setShowCreateModal(false)
      resetCreateForm()
    },
    onError: (err: unknown) => {
      setCreateError(getApiError(err).message)
      setUploadProgress(0)
    }
  })

  // Edit Material Mutation
  const updateMaterialMutation = useMutation({
    mutationFn: async (vars: { id: string; title: string; description: string; week: string }) => {
      const parsedWeek = vars.week ? Number(vars.week) : undefined
      return updateMaterial(vars.id, {
        title: vars.title,
        description: vars.description || undefined,
        week: parsedWeek
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['materials', selectedLoadId] })
      setShowEditModal(false)
      setActiveMaterial(null)
      resetEditForm()
    },
    onError: (err: unknown) => {
      setEditError(getApiError(err).message)
    }
  })

  // Replace File Mutation
  const replaceFileMutation = useMutation({
    mutationFn: async (vars: { id: string; file: File }) => {
      return replaceMaterialFile(vars.id, vars.file, (progressEvent) => {
        if (progressEvent.total) {
          const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          setReplaceProgress(percent)
        }
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['materials', selectedLoadId] })
      setShowReplaceModal(false)
      setActiveMaterial(null)
      resetReplaceForm()
    },
    onError: (err: unknown) => {
      setReplaceError(getApiError(err).message)
      setReplaceProgress(0)
    }
  })

  // Archive (Delete) Material Mutation
  const archiveMaterialMutation = useMutation({
    mutationFn: async (id: string) => {
      return archiveMaterial(id)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['materials', selectedLoadId] })
      setShowArchiveModal(false)
      setActiveMaterial(null)
    },
    onError: (err: unknown) => {
      alert(getApiError(err).message)
    }
  })

  // --- ACTIONS ---

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

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setCreateError('')
    setUploadProgress(0)

    if (!createTitle.trim()) {
      setCreateError('El título es requerido.')
      return
    }

    if (createWeek) {
      const wNum = Number(createWeek)
      if (isNaN(wNum) || wNum < 1 || wNum > 53) {
        setCreateError('La semana debe ser un número entero entre 1 y 53.')
        return
      }
    }

    if (createType === 'file') {
      if (!createFile) {
        setCreateError('Debe seleccionar un archivo para subir.')
        return
      }
      // Frontend limit: 10MB
      if (createFile.size > 10 * 1024 * 1024) {
        setCreateError('El archivo supera el límite permitido de 10 MB.')
        return
      }
    } else {
      if (!createUrl.trim()) {
        setCreateError('El enlace externo es requerido.')
        return
      }
      if (!/^https?:\/\/.+/i.test(createUrl)) {
        setCreateError('El enlace debe ser una URL válida que empiece con http:// o https://')
        return
      }
    }

    createMaterialMutation.mutate({ type: createType })
  }

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setEditError('')

    if (!activeMaterial) return

    if (!editTitle.trim()) {
      setEditError('El título es requerido.')
      return
    }

    if (editWeek) {
      const wNum = Number(editWeek)
      if (isNaN(wNum) || wNum < 1 || wNum > 53) {
        setEditError('La semana debe ser un número entero entre 1 y 53.')
        return
      }
    }

    updateMaterialMutation.mutate({
      id: activeMaterial.id,
      title: editTitle,
      description: editDesc,
      week: editWeek
    })
  }

  const handleReplaceSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setReplaceError('')
    setReplaceProgress(0)

    if (!activeMaterial || !replaceFile) {
      setReplaceError('Debe seleccionar un archivo para reemplazar.')
      return
    }

    if (replaceFile.size > 10 * 1024 * 1024) {
      setReplaceError('El archivo supera el límite permitido de 10 MB.')
      return
    }

    replaceFileMutation.mutate({
      id: activeMaterial.id,
      file: replaceFile
    })
  }

  const resetCreateForm = () => {
    setCreateTitle('')
    setCreateDesc('')
    setCreateWeek('')
    setCreateFile(null)
    setCreateUrl('')
    setCreateError('')
    setUploadProgress(0)
    if (fileInputRef.current) fileInputRef.current.value = ''
  }

  const resetEditForm = () => {
    setEditTitle('')
    setEditDesc('')
    setEditWeek('')
    setEditError('')
  }

  const resetReplaceForm = () => {
    setReplaceFile(null)
    setReplaceError('')
    setReplaceProgress(0)
    if (replaceInputRef.current) replaceInputRef.current.value = ''
  }

  const openEditModal = (mat: Material) => {
    setActiveMaterial(mat)
    setEditTitle(mat.title)
    setEditDesc(mat.description || '')
    setEditWeek(mat.week ? String(mat.week) : '')
    setEditError('')
    setShowEditModal(true)
  }

  const openReplaceModal = (mat: Material) => {
    setActiveMaterial(mat)
    resetReplaceForm()
    setShowReplaceModal(true)
  }

  const openArchiveModal = (mat: Material) => {
    setActiveMaterial(mat)
    setShowArchiveModal(true)
  }

  return (
    <section className="space-y-6 p-2 md:p-4 text-slate-800">
      {/* Top Banner Selection */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-3xl border border-slate-100/80 shadow-sm">
        <div className="space-y-1">
          <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <FileText className="text-blue-600" size={24} weight="duotone" />
            Materiales de Estudio
          </h2>
          <p className="text-xs text-slate-500">Gestione y publique los materiales de clase por curso.</p>
        </div>
        <div className="w-full md:w-80">
          <label htmlFor="course-select" className="text-[10px] font-extrabold text-slate-400 block mb-1 uppercase tracking-wider">Curso / Sección</label>
          <select
            id="course-select"
            className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white/50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            value={selectedLoadId}
            onChange={(e) => setSelectedLoadId(e.target.value)}
          >
            <option value="">-- Seleccionar Curso --</option>
            {assignments.map((assignment) => {
              const courseName = getCourseName(assignment.course_id)
              const sectionName = getSectionName(assignment.section_id)
              return (
                <option key={assignment.id} value={assignment.id}>
                  {courseName} - {sectionName}
                </option>
              )
            })}
          </select>
        </div>
      </div>

      {/* Main Content Area */}
      {!selectedLoadId ? (
        <div className="flex flex-col items-center justify-center p-16 text-center bg-white/40 border border-dashed border-slate-200 rounded-3xl min-h-[300px]">
          <div className="p-4 bg-blue-50 rounded-full text-blue-600 mb-4 animate-pulse">
            <FileText size={36} weight="duotone" />
          </div>
          <h3 className="text-base font-bold text-slate-800">Seleccione un Curso</h3>
          <p className="text-xs text-slate-500 max-w-sm mt-1 leading-relaxed">Por favor, elija un curso y sección en el selector de arriba para visualizar y administrar sus materiales.</p>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <h3 className="text-sm font-bold text-slate-900">
                {getCourseName(selectedLoad?.course_id)}
              </h3>
              <p className="text-xs text-slate-400 font-medium">
                Sección: {getSectionName(selectedLoad?.section_id)} | Docente: {getTeacherName(selectedLoad?.teacher_id)}
              </p>
            </div>
            <button
              onClick={() => setShowCreateModal(true)}
              className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-3.5 py-2 font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/10 hover:shadow-lg transition-all"
            >
              <Plus size={16} weight="bold" /> Nuevo Material
            </button>
          </div>

          {isLoadingMaterials ? (
            <div className="flex flex-col items-center justify-center py-12">
              <SpinnerGap className="spin text-blue-500" size={32} />
              <p className="text-xs text-slate-400 mt-2 font-semibold">Cargando materiales...</p>
            </div>
          ) : sortedMaterials.length === 0 ? (
            <div className="flex flex-col items-center justify-center p-12 text-center bg-white/40 border border-dashed border-slate-200 rounded-3xl">
              <p className="text-xs font-bold text-slate-500">No hay materiales cargados en este curso.</p>
              <p className="text-[11px] text-slate-400 mt-1">Haga clic en &quot;Nuevo Material&quot; para subir su primer archivo o enlace externo.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {sortedMaterials.map((material) => (
                <div
                  key={material.id}
                  className="bg-white border border-slate-100 hover:border-slate-200/80 p-5 rounded-2xl shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4"
                >
                  <div className="space-y-2">
                    <div className="flex items-center gap-1.5">
                      <span className="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[9px] font-extrabold tracking-wider uppercase">
                        Sem. {material.week || 'N/A'}
                      </span>
                      {material.type === 'file' ? (
                        <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[9px] font-extrabold tracking-wider uppercase">
                          Archivo
                        </span>
                      ) : (
                        <span className="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md text-[9px] font-extrabold tracking-wider uppercase">
                          Enlace
                        </span>
                      )}
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-slate-900 line-clamp-1">{material.title}</h4>
                      {material.description && (
                        <p className="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                          {material.description}
                        </p>
                      )}
                    </div>

                    {material.type === 'file' ? (
                      <div className="p-2.5 bg-slate-50 rounded-xl flex items-center justify-between gap-2 border border-slate-100">
                        <div className="min-w-0 flex-1">
                          <p className="text-xs font-bold text-slate-700 truncate">{material.file_name}</p>
                          <p className="text-[10px] text-slate-400 font-mono mt-0.5">{formatBytes(material.file_size)}</p>
                        </div>
                        <button
                          type="button"
                          onClick={() => handleDownload(material.id, material.file_name)}
                          className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                          title="Descargar archivo"
                        >
                          <DownloadSimple size={16} weight="bold" />
                        </button>
                      </div>
                    ) : (
                      <div className="p-2.5 bg-slate-50 rounded-xl flex items-center justify-between gap-2 border border-slate-100">
                        <div className="min-w-0 flex-1">
                          <p className="text-xs font-bold text-slate-700 truncate">{material.url}</p>
                        </div>
                        <a
                          href={material.url || '#'}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center"
                          title="Abrir enlace"
                        >
                          <ArrowSquareOut size={16} weight="bold" />
                        </a>
                      </div>
                    )}
                  </div>

                  <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-50">
                    {material.type === 'file' && (
                      <button
                        onClick={() => openReplaceModal(material)}
                        className="text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100/60 px-2.5 py-1.5 rounded-lg transition-all"
                      >
                        Reemplazar Archivo
                      </button>
                    )}
                    <button
                      onClick={() => openEditModal(material)}
                      className="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-150/50 rounded-lg transition-colors"
                      title="Editar detalles"
                    >
                      <PencilSimple size={15} weight="bold" />
                    </button>
                    <button
                      onClick={() => openArchiveModal(material)}
                      className="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                      title="Archivar material"
                    >
                      <Trash size={15} weight="bold" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* --- CREATE MATERIAL MODAL --- */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <CloudArrowUp className="text-blue-600" size={24} weight="bold" />
              Publicar Nuevo Material
            </h3>
            
            {/* Tabs for File vs External Link */}
            <div className="flex border-b border-slate-100">
              <button
                type="button"
                className={`flex-1 pb-2 text-xs font-bold transition-all border-b-2 ${createType === 'file' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'}`}
                onClick={() => {
                  setCreateType('file')
                  setCreateError('')
                }}
              >
                Archivo Local
              </button>
              <button
                type="button"
                className={`flex-1 pb-2 text-xs font-bold transition-all border-b-2 ${createType === 'link' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'}`}
                onClick={() => {
                  setCreateType('link')
                  setCreateError('')
                }}
              >
                Enlace Externo
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Título *</label>
                <input
                  type="text"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ej. Guía de Vectores y Cinemática"
                  value={createTitle}
                  onChange={(e) => setCreateTitle(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Descripción (Opcional)</label>
                <textarea
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[70px]"
                  placeholder="Instrucciones o notas adicionales para los alumnos..."
                  value={createDesc}
                  onChange={(e) => setCreateDesc(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Semana (1 - 53) (Opcional)</label>
                <input
                  type="number"
                  min="1"
                  max="53"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ej. 1"
                  value={createWeek}
                  onChange={(e) => setCreateWeek(e.target.value)}
                />
              </div>

              {createType === 'file' ? (
                <div className="p-4 bg-slate-50 border border-slate-100 rounded-xl space-y-2">
                  <label className="text-[10px] font-extrabold text-slate-700 block cursor-pointer">
                    Seleccionar Archivo (Límite: 10MB) *
                    <input
                      type="file"
                      ref={fileInputRef}
                      className="mt-2 text-xs text-slate-500 block w-full file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 file:cursor-pointer hover:file:bg-blue-100"
                      onChange={(e) => setCreateFile(e.target.files?.[0] || null)}
                    />
                  </label>
                  {createFile && (
                    <div className="text-[10px] text-slate-500 flex justify-between font-mono">
                      <span>Tamaño: {formatBytes(createFile.size)}</span>
                      <span>Tipo: {createFile.type || 'Desconocido'}</span>
                    </div>
                  )}
                </div>
              ) : (
                <div>
                  <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Enlace Externo (URL) *</label>
                  <input
                    type="text"
                    className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="https://example.com/recurso"
                    value={createUrl}
                    onChange={(e) => setCreateUrl(e.target.value)}
                  />
                </div>
              )}

              {/* Progress Bar Component */}
              {createMaterialMutation.isPending && createType === 'file' && uploadProgress > 0 && (
                <div className="space-y-1.5">
                  <div className="flex justify-between items-center text-[10px] font-bold text-slate-500">
                    <span>Subiendo archivo...</span>
                    <span>{uploadProgress}%</span>
                  </div>
                  <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div
                      className="bg-blue-600 h-2 rounded-full transition-all duration-150"
                      style={{ width: `${uploadProgress}%` }}
                    />
                  </div>
                </div>
              )}

              {createError && (
                <p className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium flex items-start gap-1.5">
                  <Warning size={14} className="mt-0.5 flex-shrink-0" />
                  <span>{createError}</span>
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                  disabled={createMaterialMutation.isPending}
                  onClick={() => {
                    setShowCreateModal(false)
                    resetCreateForm()
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50 flex items-center gap-1.5"
                  disabled={createMaterialMutation.isPending}
                >
                  {createMaterialMutation.isPending ? (
                    <>
                      <SpinnerGap className="spin" size={14} /> Guardando...
                    </>
                  ) : (
                    'Publicar'
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- EDIT DETAILS MODAL --- */}
      {showEditModal && activeMaterial && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <PencilSimple className="text-blue-600" size={24} weight="bold" />
              Editar Detalles del Material
            </h3>

            <form onSubmit={handleEditSubmit} className="space-y-4">
              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Título *</label>
                <input
                  type="text"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ej. Guía de Vectores y Cinemática"
                  value={editTitle}
                  onChange={(e) => setEditTitle(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Descripción (Opcional)</label>
                <textarea
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[70px]"
                  value={editDesc}
                  onChange={(e) => setEditDesc(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">Semana (1 - 53) (Opcional)</label>
                <input
                  type="number"
                  min="1"
                  max="53"
                  className="w-full p-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value={editWeek}
                  onChange={(e) => setEditWeek(e.target.value)}
                />
              </div>

              {editError && (
                <p className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium flex items-start gap-1.5">
                  <Warning size={14} className="mt-0.5 flex-shrink-0" />
                  <span>{editError}</span>
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                  disabled={updateMaterialMutation.isPending}
                  onClick={() => {
                    setShowEditModal(false)
                    setActiveMaterial(null)
                    resetEditForm()
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50 flex items-center gap-1.5"
                  disabled={updateMaterialMutation.isPending}
                >
                  {updateMaterialMutation.isPending ? (
                    <>
                      <SpinnerGap className="spin" size={14} /> Guardando...
                    </>
                  ) : (
                    'Guardar Cambios'
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- REPLACE FILE MODAL --- */}
      {showReplaceModal && activeMaterial && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <CloudArrowUp className="text-blue-600" size={24} weight="bold" />
              Reemplazar Archivo del Material
            </h3>
            <p className="text-[11px] text-slate-500">
              Reemplazará el archivo para: <strong>{activeMaterial.title}</strong>. El nombre del archivo y tamaño se actualizarán.
            </p>

            <form onSubmit={handleReplaceSubmit} className="space-y-4">
              <div className="p-4 bg-slate-50 border border-slate-100 rounded-xl space-y-2">
                <label className="text-[10px] font-extrabold text-slate-700 block cursor-pointer">
                  Seleccionar Nuevo Archivo (Límite: 10MB) *
                  <input
                    type="file"
                    ref={replaceInputRef}
                    className="mt-2 text-xs text-slate-500 block w-full file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 file:cursor-pointer hover:file:bg-blue-100"
                    onChange={(e) => setReplaceFile(e.target.files?.[0] || null)}
                  />
                </label>
                {replaceFile && (
                  <div className="text-[10px] text-slate-500 flex justify-between font-mono">
                    <span>Tamaño: {formatBytes(replaceFile.size)}</span>
                  </div>
                )}
              </div>

              {/* Replace Progress Bar */}
              {replaceFileMutation.isPending && replaceProgress > 0 && (
                <div className="space-y-1.5">
                  <div className="flex justify-between items-center text-[10px] font-bold text-slate-500">
                    <span>Reemplazando archivo...</span>
                    <span>{replaceProgress}%</span>
                  </div>
                  <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div
                      className="bg-blue-600 h-2 rounded-full transition-all duration-150"
                      style={{ width: `${replaceProgress}%` }}
                    />
                  </div>
                </div>
              )}

              {replaceError && (
                <p className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium flex items-start gap-1.5">
                  <Warning size={14} className="mt-0.5 flex-shrink-0" />
                  <span>{replaceError}</span>
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                  disabled={replaceFileMutation.isPending}
                  onClick={() => {
                    setShowReplaceModal(false)
                    setActiveMaterial(null)
                    resetReplaceForm()
                  }}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50 flex items-center gap-1.5"
                  disabled={replaceFileMutation.isPending}
                >
                  {replaceFileMutation.isPending ? (
                    <>
                      <SpinnerGap className="spin" size={14} /> Subiendo...
                    </>
                  ) : (
                    'Reemplazar'
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- ARCHIVE CONFIRMATION MODAL --- */}
      {showArchiveModal && activeMaterial && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in" role="dialog" aria-modal="true">
          <div className="bg-white border border-slate-100 p-6 rounded-3xl max-w-sm w-full shadow-2xl space-y-4 text-slate-800">
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <Warning className="text-red-500" size={24} weight="fill" />
              Archivar Material
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed">
              ¿Está seguro de que desea archivar el material &quot;<strong>{activeMaterial.title}</strong>&quot;? Esta acción no se puede deshacer y los alumnos ya no tendrán acceso a él.
            </p>

            <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                className="button button-secondary rounded-xl text-slate-650 text-xs px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold"
                disabled={archiveMaterialMutation.isPending}
                onClick={() => {
                  setShowArchiveModal(false)
                  setActiveMaterial(null)
                }}
              >
                Cancelar
              </button>
              <button
                type="button"
                className="button button-primary bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs px-4 py-2 font-bold shadow-md disabled:opacity-50"
                disabled={archiveMaterialMutation.isPending}
                onClick={() => archiveMaterialMutation.mutate(activeMaterial.id)}
              >
                {archiveMaterialMutation.isPending ? 'Archivando...' : 'Sí, Archivar'}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
