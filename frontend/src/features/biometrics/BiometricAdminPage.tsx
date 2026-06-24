import { useState, useRef, useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Fingerprint,
  Warning,
  SpinnerGap,
  Camera,
  Trash,
  Key,
  ShieldCheck
} from '@phosphor-icons/react'
import { useAuth } from '@/features/auth/AuthContext'
import { DataTable } from '@/components/shared/DataTable'
import { OperationalState } from '@/components/shared/OperationalState'
import { getApiError } from '@/lib/api/client'
import {
  listBiometricConsents,
  searchStudents,
  grantBiometricConsent,
  revokeBiometricConsent,
  enrollBiometricProfile,
  listStations,
  createStation,
  revokeStation,
  listStationCameras,
  createStationCamera,
  createStationActivationCode
} from './api'
import type { StudentLookup } from './api'

export function BiometricAdminPage() {
  const { user } = useAuth()
  const client = useQueryClient()

  // 1. Permission check
  const canManageDevices = user?.roles.includes('superadmin') || user?.permissions.includes('gestionar_dispositivos')

  // Tabs state: 'consents' | 'enrollment' | 'stations'
  const [activeTab, setActiveTab] = useState<'consents' | 'enrollment' | 'stations'>('consents')

  // Shared Query Cache invalidation helper
  const invalidate = async (key: string) => client.invalidateQueries({ queryKey: [key] })

  // Queries
  const consentsQuery = useQuery({ queryKey: ['consents'], queryFn: listBiometricConsents })
  const stationsQuery = useQuery({ queryKey: ['stations'], queryFn: listStations })

  // --- TAB 1: Consents state & mutations ---
  const [consentStudentId, setConsentStudentId] = useState('')
  const [consentStudentSearch, setConsentStudentSearch] = useState('')
  const [consentStudentResults, setConsentStudentResults] = useState<StudentLookup[]>([])
  const [selectedConsentStudent, setSelectedConsentStudent] = useState<StudentLookup | null>(null)
  const [consentAccepted, setConsentAccepted] = useState(false)
  const [legalBasis, setLegalBasis] = useState('')
  const [expiresAt, setExpiresAt] = useState('')
  const [consentMessage, setConsentMessage] = useState('')
  const [consentError, setConsentError] = useState('')
  const [revocationConsentId, setRevocationConsentId] = useState<string | null>(null)
  const [revocationReason, setRevocationReason] = useState('')

  const searchConsentStudentsMutation = useMutation({
    mutationFn: searchStudents,
    onSuccess: (students) => {
      setConsentStudentResults(students)
      setConsentError(students.length === 0 ? 'No se encontraron alumnos con ese DNI o nombre.' : '')
    },
    onError: (err) => {
      setConsentStudentResults([])
      setConsentError(getApiError(err).message)
    }
  })

  const grantConsentMutation = useMutation({
    mutationFn: grantBiometricConsent,
    onSuccess: async () => {
      setConsentStudentId('')
      setConsentStudentSearch('')
      setConsentStudentResults([])
      setSelectedConsentStudent(null)
      setConsentAccepted(false)
      setLegalBasis('')
      setExpiresAt('')
      setConsentMessage('Consentimiento biométrico otorgado con éxito.')
      setConsentError('')
      await invalidate('consents')
    },
    onError: (err) => {
      setConsentMessage('')
      setConsentError(getApiError(err).message)
    }
  })

  const revokeConsentMutation = useMutation({
    mutationFn: ({ consentId, reason }: { consentId: string; reason: string }) =>
      revokeBiometricConsent(consentId, reason),
    onSuccess: async () => {
      setRevocationConsentId(null)
      setRevocationReason('')
      await invalidate('consents')
    }
  })

  // --- TAB 2: Guided Enrollment state & mutations ---
  const [enrollStudentId, setEnrollStudentId] = useState('')
  const [enrollStudentSearch, setEnrollStudentSearch] = useState('')
  const [enrollStudentResults, setEnrollStudentResults] = useState<StudentLookup[]>([])
  const [selectedEnrollStudent, setSelectedEnrollStudent] = useState<StudentLookup | null>(null)
  const [images, setImages] = useState<File[]>([])
  const [imagePreviews, setImagePreviews] = useState<string[]>([])
  const [cameraActive, setCameraActive] = useState(false)
  const [enrollMessage, setEnrollMessage] = useState('')
  const [enrollError, setEnrollError] = useState('')
  const [cameraError, setCameraError] = useState('')
  const [isEnrolling, setIsEnrolling] = useState(false)

  const videoRef = useRef<HTMLVideoElement | null>(null)
  const streamRef = useRef<MediaStream | null>(null)

  const searchEnrollStudentsMutation = useMutation({
    mutationFn: searchStudents,
    onSuccess: (students) => {
      setEnrollStudentResults(students)
      setEnrollError(students.length === 0 ? 'No se encontraron alumnos con ese DNI o nombre.' : '')
    },
    onError: (err) => {
      setEnrollStudentResults([])
      setEnrollError(getApiError(err).message)
    }
  })

  // Find active consent for target student
  const activeConsent = consentsQuery.data?.data.find(
    (c) =>
      c.student_id?.toLowerCase() === enrollStudentId.trim().toLowerCase() &&
      c.status === 'active'
  )

  // Clean up object URLs to avoid memory leaks
  useEffect(() => {
    return () => {
      imagePreviews.forEach((url) => URL.revokeObjectURL(url))
    }
  }, [imagePreviews])

  // Camera control
  const startCamera = async () => {
    try {
      setCameraError('')
      if (!navigator.mediaDevices?.getUserMedia) {
        setCameraError('Tu navegador no permite acceso a camara en este contexto. Usa http://localhost:5173 o habilita permisos.')
        return
      }
      const stream = await navigator.mediaDevices.getUserMedia({ video: true })
      streamRef.current = stream
      setCameraActive(true)
      if (videoRef.current) {
        videoRef.current.srcObject = stream
      }
    } catch (err: unknown) {
      const message = err instanceof DOMException && err.name === 'NotAllowedError'
        ? 'Permiso de camara denegado. Habilitalo en el navegador para localhost:5173.'
        : 'No se pudo encender la camara. Verifica que exista una camara conectada y que no este en uso.'
      setCameraError(message)
      setCameraActive(false)
    }
  }

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop())
      streamRef.current = null
    }
    setCameraActive(false)
  }

  const capturePhoto = () => {
    if (videoRef.current && images.length < 5) {
      const canvas = document.createElement('canvas')
      canvas.width = videoRef.current.videoWidth || 640
      canvas.height = videoRef.current.videoHeight || 480
      const ctx = canvas.getContext('2d')
      if (ctx) {
        ctx.drawImage(videoRef.current, 0, 0, canvas.width, canvas.height)
        canvas.toBlob((blob) => {
          if (blob) {
            const file = new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' })
            const url = URL.createObjectURL(file)
            setImages((prev) => [...prev, file])
            setImagePreviews((prev) => [...prev, url])
          }
        }, 'image/jpeg')
      }
    }
  }

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const filesArray = Array.from(e.target.files)
      const allowedCount = 5 - images.length
      const addedFiles = filesArray.slice(0, allowedCount)

      const addedPreviews = addedFiles.map((file) => URL.createObjectURL(file))
      setImages((prev) => [...prev, ...addedFiles])
      setImagePreviews((prev) => [...prev, ...addedPreviews])
    }
  }

  const removePhoto = (index: number) => {
    URL.revokeObjectURL(imagePreviews[index])
    setImages((prev) => prev.filter((_, i) => i !== index))
    setImagePreviews((prev) => prev.filter((_, i) => i !== index))
  }

  const enrollProfileMutation = useMutation({
    mutationFn: ({ studentId, consentId }: { studentId: string; consentId: string }) =>
      enrollBiometricProfile(studentId, consentId, images),
    onMutate: () => {
      setIsEnrolling(true)
      setEnrollMessage('')
      setEnrollError('')
    },
    onSuccess: async () => {
      setImages([])
      setImagePreviews([])
      setEnrollStudentId('')
      setEnrollStudentSearch('')
      setEnrollStudentResults([])
      setSelectedEnrollStudent(null)
      setEnrollMessage('Perfil facial enrolado con éxito.')
      setEnrollError('')
      setIsEnrolling(false)
      stopCamera()
    },
    onError: (err) => {
      setEnrollMessage('')
      setEnrollError(getApiError(err).message)
      setIsEnrolling(false)
    }
  })

  // --- TAB 3: Devices & Stations state & mutations ---
  const [stationName, setStationName] = useState('')
  const [stationLocation, setStationLocation] = useState('')
  const [stationMode, setStationMode] = useState<'entry' | 'exit' | 'mixed'>('mixed')
  const [stationMessage, setStationMessage] = useState('')
  const [stationError, setStationError] = useState('')

  const [revocationStationId, setRevocationStationId] = useState<string | null>(null)
  const [stationRevokeReason, setStationRevokeReason] = useState('')

  const [selectedStationId, setSelectedStationId] = useState<string | null>(null)
  const [cameraLabel, setCameraLabel] = useState('')
  const [cameraIdentifier, setCameraIdentifier] = useState('')



  const [activationCodeInfo, setActivationCodeInfo] = useState<{ code: string; stationId: string } | null>(null)

  // Sub-query for selected station cameras
  const camerasQuery = useQuery({
    queryKey: ['cameras', selectedStationId],
    queryFn: () => listStationCameras(selectedStationId!),
    enabled: !!selectedStationId
  })

  const createStationMutation = useMutation({
    mutationFn: createStation,
    onSuccess: async () => {
      setStationName('')
      setStationLocation('')
      setStationMode('mixed')
      setStationMessage('Estación de asistencia registrada.')
      setStationError('')
      await invalidate('stations')
    },
    onError: (err) => {
      setStationMessage('')
      setStationError(getApiError(err).message)
    }
  })

  const revokeStationMutation = useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => revokeStation(id, reason),
    onSuccess: async () => {
      setRevocationStationId(null)
      setStationRevokeReason('')
      await invalidate('stations')
    }
  })

  const createCameraMutation = useMutation({
    mutationFn: ({ stationId, label, identifier }: { stationId: string; label: string; identifier: string }) =>
      createStationCamera(stationId, { label, device_identifier: identifier, active: true }),
    onSuccess: async () => {
      setCameraLabel('')
      setCameraIdentifier('')
      await invalidate('cameras')
    }
  })

  const generateActivationMutation = useMutation({
    mutationFn: createStationActivationCode,
    onSuccess: (data, stationId) => {
      setActivationCodeInfo({ code: data.activation_code, stationId })
    }
  })

  // Permisos de UI generales
  if (!canManageDevices) {
    return (
      <OperationalState
        state="forbidden"
        title="Acceso Denegado"
        message="Su cuenta no tiene los permisos necesarios (gestionar_dispositivos) para administrar la infraestructura biométrica."
      />
    )
  }

  return (
    <section className="page-stack dashboard-light-bg p-6 rounded-3xl border border-slate-100/80 shadow-sm text-slate-800">
      <header className="space-y-1">
        <p className="eyebrow text-blue-600 font-extrabold tracking-wider text-xs">Seguridad e Infraestructura</p>
        <h1 className="text-3xl font-black text-slate-900 tracking-tight">Biometría y Dispositivos</h1>
        <p className="text-slate-500 text-sm">Administra el consentimiento, enrola rostros de alumnos y autoriza estaciones de asistencia de forma segura.</p>
      </header>

      {/* Tabs navigation */}
      <div className="flex bg-slate-100 p-1.5 rounded-2xl mb-4 max-w-lg gap-1.5 shadow-inner border border-slate-200/50">
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center ${
            activeTab === 'consents' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => setActiveTab('consents')}
        >
          Consentimientos
        </button>
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center ${
            activeTab === 'enrollment' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => {
            setActiveTab('enrollment')
            stopCamera()
          }}
        >
          Enrolamiento Facial
        </button>
        <button
          className={`flex-1 px-4 py-2.5 font-bold text-xs rounded-xl transition-all text-center ${
            activeTab === 'stations' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/50'
          }`}
          onClick={() => {
            setActiveTab('stations')
            stopCamera()
          }}
        >
          Estaciones y Cámaras
        </button>
      </div>

      {/* TAB 1: Consentimientos */}
      {activeTab === 'consents' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-1">
            <form
              className="glass-panel-light p-6 rounded-2xl flex flex-col gap-5 text-slate-800"
              onSubmit={(e) => {
                e.preventDefault()
                if (!consentStudentId || !consentAccepted) return
                const authorizationText = 'Autorizacion biometrica marcada en plataforma para registro facial y asistencia.'
                grantConsentMutation.mutate({
                  student_id: consentStudentId,
                  legal_basis: legalBasis || authorizationText,
                  expires_at: expiresAt || undefined
                })
              }}
            >
              <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                <Fingerprint className="text-blue-600" size={22} weight="bold" />
                Otorgar Consentimiento
              </h2>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Alumno por DNI o nombre
                <span className="flex gap-2">
                  <input
                    type="text"
                    className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm flex-1"
                    placeholder="DNI o nombre del alumno"
                    value={consentStudentSearch}
                    onChange={(e) => {
                      setConsentStudentSearch(e.target.value)
                      setSelectedConsentStudent(null)
                      setConsentStudentId('')
                    }}
                  />
                  <button
                    className="button button-secondary mt-1 rounded-xl px-3 text-sm"
                    type="button"
                    disabled={consentStudentSearch.trim().length < 3 || searchConsentStudentsMutation.isPending}
                    onClick={() => searchConsentStudentsMutation.mutate(consentStudentSearch.trim())}
                  >
                    Buscar
                  </button>
                </span>
              </label>
              {consentStudentResults.length > 0 && (
                <div className="space-y-2">
                  {consentStudentResults.map((student) => (
                    <button
                      key={student.id}
                      type="button"
                      className={`w-full text-left rounded-xl border p-3 text-sm transition ${
                        consentStudentId === student.id
                          ? 'border-blue-300 bg-blue-50 text-blue-800'
                          : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'
                      }`}
                      onClick={() => {
                        setSelectedConsentStudent(student)
                        setConsentStudentId(student.id)
                        setConsentStudentSearch(`${student.dni} - ${student.name}`)
                      }}
                    >
                      <strong className="block">{student.name}</strong>
                      <span className="text-xs text-slate-500">DNI {student.dni}</span>
                    </button>
                  ))}
                </div>
              )}
              {selectedConsentStudent && (
                <p className="form-success bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-3 rounded-xl">
                  Alumno seleccionado: {selectedConsentStudent.name} - DNI {selectedConsentStudent.dni}
                </p>
              )}
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Base Legal / Documentación
                <input
                  type="text"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                  placeholder="Ej. Consentimiento firmado 2026-06-08"
                  value={legalBasis}
                  onChange={(e) => setLegalBasis(e.target.value)}
                />
              </label>
              <label className="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-3 text-sm text-slate-700 cursor-pointer">
                <input
                  type="checkbox"
                  className="mt-1 accent-blue-600"
                  checked={consentAccepted}
                  onChange={(e) => setConsentAccepted(e.target.checked)}
                />
                <span>
                  Confirmo que el alumno/apoderado autorizo el uso de datos biometricos faciales para enrolamiento y registro de asistencia, segun la politica del colegio.
                </span>
              </label>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Fecha de Expiración (Opcional)
                <input
                  type="datetime-local"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                  value={expiresAt}
                  onChange={(e) => setExpiresAt(e.target.value)}
                />
              </label>

              <button className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm disabled:opacity-50" type="submit" disabled={grantConsentMutation.isPending || !consentStudentId || !consentAccepted}>
                Registrar Consentimiento
              </button>

              {consentMessage && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">{consentMessage}</p>}
              {consentError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">{consentError}</p>}
            </form>
          </div>

          <div className="md:col-span-2 glass-panel-light p-6 rounded-2xl flex flex-col gap-4 text-slate-800">
            <h2 className="text-lg font-bold text-slate-900">Historial de Consentimientos</h2>
            <div className="table-scroll">
              <DataTable
                rows={consentsQuery.data?.data}
                isLoading={consentsQuery.isLoading}
                error={consentsQuery.error as Error}
                columns={[
                  {
                    label: 'ID Alumno',
                    render: (consent) => (
                      <div className="space-y-1 py-1">
                        <strong className="text-slate-900 font-bold text-sm">{consent.student_name || 'Alumno'}</strong>
                        <small className="text-slate-500 font-mono text-xs block">{consent.student_id}</small>
                      </div>
                    )
                  },
                  {
                    label: 'Base Legal',
                    render: (consent) => <span className="text-slate-700 text-sm font-medium">{consent.legal_basis}</span>
                  },
                  {
                    label: 'Estado',
                    render: (consent) => (
                      <span
                        className={`status-chip border inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                          consent.status === 'active'
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : consent.status === 'revoked'
                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                            : 'bg-slate-50 text-slate-700 border-slate-200'
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full mr-1.5 ${
                          consent.status === 'active' ? 'bg-emerald-500' : consent.status === 'revoked' ? 'bg-rose-500' : 'bg-slate-500'
                        }`} />
                        {consent.status === 'active'
                          ? 'Activo'
                          : consent.status === 'revoked'
                          ? 'Revocado'
                          : 'Expirado'}
                      </span>
                    )
                  },
                  {
                    label: 'Acciones',
                    render: (consent) =>
                      consent.status === 'active' ? (
                        <button
                          className="button button-secondary text-rose-600 border-rose-200 hover:bg-rose-50/50 hover:border-rose-300 text-xs px-3 py-1.5 rounded-xl font-semibold transition-all shadow-sm"
                          onClick={() => setRevocationConsentId(consent.id)}
                        >
                          Revocar
                        </button>
                      ) : (
                        <span className="text-slate-400 text-xs italic">Sin acciones</span>
                      )
                  }
                ]}
              />
            </div>
          </div>

          {/* Revocation Modal Overlay */}
          {revocationConsentId && (
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
              <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
                <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
                  <Warning className="text-rose-500" size={24} weight="fill" />
                  Revocar Consentimiento Biométrico
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed">
                  Esta acción programará la eliminación inmediata de los perfiles faciales asociados. Debes registrar el motivo.
                </p>
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                  Motivo de Revocación
                  <input
                    type="text"
                    className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                    placeholder="Ej. Solicitado por el apoderado"
                    value={revocationReason}
                    onChange={(e) => setRevocationReason(e.target.value)}
                    required
                  />
                </label>
                <div className="flex justify-end gap-2 pt-2">
                  <button
                    className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                    onClick={() => {
                      setRevocationConsentId(null)
                      setRevocationReason('')
                    }}
                  >
                    Cancelar
                  </button>
                  <button
                    className="button button-primary bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-rose-500/20 transition-all"
                    disabled={!revocationReason || revokeConsentMutation.isPending}
                    onClick={() =>
                      revokeConsentMutation.mutate({
                        consentId: revocationConsentId,
                        reason: revocationReason
                      })
                    }
                  >
                    Confirmar Revocación
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* TAB 2: Enrolamiento Guided Flow */}
      {activeTab === 'enrollment' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="glass-panel-light p-6 rounded-2xl flex flex-col justify-between text-slate-800">
            <div>
              <h2 className="text-lg font-bold text-slate-900 mb-2">Verificar Consentimiento</h2>
              <p className="text-sm text-slate-500 mb-4">
                Busca al alumno por DNI o nombre para comprobar si tiene un consentimiento activo.
              </p>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Alumno por DNI o nombre
                <span className="flex gap-2">
                  <input
                    type="text"
                    className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm flex-1"
                    placeholder="DNI o nombre del alumno"
                    value={enrollStudentSearch}
                    onChange={(e) => {
                      setEnrollStudentSearch(e.target.value)
                      setSelectedEnrollStudent(null)
                      setEnrollStudentId('')
                    }}
                  />
                  <button
                    className="button button-secondary mt-1 rounded-xl px-3 text-sm"
                    type="button"
                    disabled={enrollStudentSearch.trim().length < 3 || searchEnrollStudentsMutation.isPending}
                    onClick={() => searchEnrollStudentsMutation.mutate(enrollStudentSearch.trim())}
                  >
                    Buscar
                  </button>
                </span>
              </label>
              {enrollStudentResults.length > 0 && (
                <div className="mt-3 space-y-2">
                  {enrollStudentResults.map((student) => (
                    <button
                      key={student.id}
                      type="button"
                      className={`w-full text-left rounded-xl border p-3 text-sm transition ${
                        enrollStudentId === student.id
                          ? 'border-blue-300 bg-blue-50 text-blue-800'
                          : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'
                      }`}
                      onClick={() => {
                        setSelectedEnrollStudent(student)
                        setEnrollStudentId(student.id)
                        setEnrollStudentSearch(`${student.dni} - ${student.name}`)
                      }}
                    >
                      <strong className="block">{student.name}</strong>
                      <span className="text-xs text-slate-500">DNI {student.dni}</span>
                    </button>
                  ))}
                </div>
              )}
              {selectedEnrollStudent && (
                <p className="form-success bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-3 rounded-xl mt-3">
                  Alumno seleccionado: {selectedEnrollStudent.name} - DNI {selectedEnrollStudent.dni}
                </p>
              )}

              {/* Consent alert banners */}
              {enrollStudentId.trim() !== '' && (
                <div className="mt-4">
                  {activeConsent ? (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3 text-emerald-800">
                      <ShieldCheck size={24} weight="fill" className="text-emerald-600 mt-0.5" />
                      <div>
                        <strong className="font-bold">Consentimiento Activo Encontrado</strong>
                        <p className="text-xs mt-1 text-emerald-700">
                          Base Legal: {activeConsent.legal_basis} (Válido)
                        </p>
                      </div>
                    </div>
                  ) : (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3 text-rose-800">
                      <Warning size={24} weight="fill" className="text-rose-600 mt-0.5" />
                      <div>
                        <strong className="font-bold">Enrolamiento Bloqueado</strong>
                        <p className="text-xs mt-1 text-rose-700">
                          El alumno no cuenta con consentimiento biométrico activo. Registra uno antes de continuar.
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* General enrollment messages */}
            <div className="mt-4">
              {enrollMessage && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">{enrollMessage}</p>}
              {enrollError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">{enrollError}</p>}
            </div>
          </div>

          {/* Capture Console */}
          <div className="glass-panel-light p-6 rounded-2xl space-y-4 text-slate-800">
            <h2 className="text-lg font-bold text-slate-900">Captura Facial (3 a 5 Fotos)</h2>

            {!activeConsent ? (
              <div className="h-64 border border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-500 bg-slate-50/50 p-6 text-center">
                <Fingerprint size={48} weight="thin" className="text-slate-400 mb-2 animate-pulse" />
                <p className="text-sm font-semibold text-slate-700">Selecciona un alumno con consentimiento activo para desbloquear la camara.</p>
              </div>
            ) : (
              <div className="space-y-4">
                {cameraError && (
                  <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">
                    {cameraError}
                  </p>
                )}

                {/* Webcam capture feed */}
                {cameraActive ? (
                  <div className="relative rounded-2xl overflow-hidden bg-slate-900 aspect-video border border-slate-200 shadow-inner">
                    <video ref={videoRef} autoPlay playsInline className="w-full h-full object-cover" />
                    {/* Face alignment guide */}
                    <div className="absolute inset-0 border-4 border-dashed border-blue-400/50 rounded-full m-8 pointer-events-none flex items-center justify-center">
                      <span className="bg-slate-900/75 text-blue-200 text-xs px-2 py-1 rounded-md font-semibold">Alinea el rostro aquí</span>
                    </div>
                    <div className="absolute bottom-4 left-0 right-0 flex justify-center gap-2 px-4">
                      <button
                        className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs py-2 shadow-md shadow-blue-500/20"
                        onClick={capturePhoto}
                        disabled={images.length >= 5}
                      >
                        <Camera size={16} /> Capturar Foto
                      </button>
                      <button className="button button-secondary text-slate-700 border-slate-200 bg-white hover:bg-slate-50 text-xs py-2" onClick={stopCamera}>
                        Apagar Cámara
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="h-48 rounded-2xl flex flex-col items-center justify-center bg-slate-50 border border-slate-200 shadow-inner">
                    <button className="button button-secondary bg-white border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl px-4 py-2.5 font-semibold text-sm shadow-sm transition-all" onClick={startCamera}>
                      <Camera size={18} className="text-blue-600" /> Encender Cámara
                    </button>
                  </div>
                )}

                {/* File picker alternative */}
                <div className="flex items-center justify-between border-t border-slate-100 pt-4">
                  <div>
                    <strong className="text-sm block text-slate-900 font-bold">Carga de fotos reales</strong>
                    <span className="text-xs text-slate-500">Alternativa cuando este equipo no tiene camara disponible</span>
                  </div>
                  <label className="button button-secondary bg-white border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl px-4 py-2 font-semibold text-xs cursor-pointer shadow-sm">
                    Seleccionar Archivos
                    <input
                      type="file"
                      multiple
                      accept="image/*"
                      className="hidden"
                      onChange={handleFileUpload}
                      disabled={images.length >= 5}
                    />
                  </label>
                </div>

                {/* Captured previews grid */}
                {images.length > 0 && (
                  <div className="space-y-2">
                    <strong className="text-sm block text-slate-900 font-bold">Fotos Capturadas ({images.length} de 5)</strong>
                    <div className="grid grid-cols-5 gap-2">
                      {imagePreviews.map((url, idx) => (
                        <div key={url} className="relative aspect-square rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                          <img src={url} alt="Snap preview" className="w-full h-full object-cover" />
                          <button
                            type="button"
                            className="absolute top-1 right-1 p-1 bg-rose-600 hover:bg-rose-700 text-white rounded-full transition-colors shadow-md"
                            onClick={() => removePhoto(idx)}
                          >
                            <Trash size={12} />
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Submit action */}
                <button
                  className="button button-primary w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm"
                  disabled={images.length < 3 || isEnrolling}
                  onClick={() =>
                    enrollProfileMutation.mutate({
                      studentId: enrollStudentId,
                      consentId: activeConsent.id
                    })
                  }
                >
                  {isEnrolling ? (
                    <>
                      <SpinnerGap className="spin" size={18} /> Enrolando...
                    </>
                  ) : (
                    'Finalizar y Registrar Enrolamiento'
                  )}
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* TAB 3: Estaciones y Cámaras */}
      {activeTab === 'stations' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-1 space-y-6">
            <form
              className="glass-panel-light p-6 rounded-2xl flex flex-col gap-5 text-slate-800 shadow-sm"
              onSubmit={(e) => {
                e.preventDefault()
                createStationMutation.mutate({
                  name: stationName,
                  location: stationLocation,
                  mode: stationMode
                })
              }}
            >
              <h2 className="text-lg font-bold text-slate-900">Nueva Estación</h2>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Nombre
                <input
                  type="text"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                  placeholder="Ej. Puerta Principal"
                  value={stationName}
                  onChange={(e) => setStationName(e.target.value)}
                  required
                />
              </label>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Ubicación
                <input
                  type="text"
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                  placeholder="Ej. Pabellón Secundaria"
                  value={stationLocation}
                  onChange={(e) => setStationLocation(e.target.value)}
                  required
                />
              </label>
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1.5 cursor-pointer">
                Modo de Operación
                <select
                  className="glass-input-light mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal cursor-pointer shadow-sm"
                  value={stationMode}
                  onChange={(e) => setStationMode(e.target.value as 'entry' | 'exit' | 'mixed')}
                >
                  <option value="entry">Entrada</option>
                  <option value="exit">Salida</option>
                  <option value="mixed">Mixto (Entrada/Salida)</option>
                </select>
              </label>

              <button className="button button-primary bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold shadow-md shadow-blue-500/20 transition-all text-sm" type="submit" disabled={createStationMutation.isPending}>
                Registrar Estación
              </button>

              {stationMessage && <p className="form-success bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded-xl">{stationMessage}</p>}
              {stationError && <p className="form-error bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-xl">{stationError}</p>}
            </form>

            {/* Temporary Activation Code Display */}
            {activationCodeInfo && (
              <div className="glass-panel-light p-6 rounded-2xl border-blue-200 bg-blue-50/50 text-slate-800 shadow-sm animate-fade-in">
                <div className="flex items-start gap-3">
                  <Key size={24} className="text-blue-600 mt-1" />
                  <div className="flex-1">
                    <strong className="block text-blue-900 font-bold">Código de Activación</strong>
                    <p className="text-xs text-blue-700 mt-0.5 font-medium">
                      Ingresa este código en la estación para vincular el dispositivo.
                    </p>
                    <div className="mt-3 text-3xl font-mono font-bold tracking-widest text-blue-950 bg-blue-100/60 py-2.5 px-4 rounded-xl text-center shadow-inner border border-blue-200/50">
                      {activationCodeInfo.code}
                    </div>
                    <span className="text-[10px] text-blue-500 block mt-2 text-center font-bold">
                      Válido por 10 minutos
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className="md:col-span-2 space-y-6">
            <div className="glass-panel-light p-6 rounded-2xl space-y-5 text-slate-800 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900">Estaciones de Asistencia</h2>
              
              {stationsQuery.isLoading ? (
                <div className="flex items-center justify-center p-12">
                  <SpinnerGap className="spin text-blue-600" size={32} />
                </div>
              ) : stationsQuery.error ? (
                <div className="p-4 text-sm bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                  Error: {(stationsQuery.error as Error).message}
                </div>
              ) : stationsQuery.data?.data && stationsQuery.data.data.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {stationsQuery.data.data.map((st) => (
                    <div key={st.id} className="bg-white border border-slate-100 rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between gap-4">
                      {/* Header row */}
                      <div className="flex justify-between items-start">
                        <div>
                          <strong className="text-slate-900 text-base font-bold block">{st.name}</strong>
                          <span className="text-slate-500 text-xs block mt-0.5 font-medium">{st.location}</span>
                        </div>
                        {/* Mode badge */}
                        <span className="capitalize text-[10px] font-extrabold tracking-wider px-2.5 py-1 bg-slate-50 border border-slate-100 rounded-md text-slate-600 shadow-sm">
                          {st.mode === 'mixed' ? 'Mixto' : st.mode === 'entry' ? 'Entrada' : 'Salida'}
                        </span>
                      </div>

                      {/* Radar/Camera visualization widget */}
                      <div className="bg-slate-50 rounded-xl h-24 flex items-center justify-center relative overflow-hidden border border-slate-100/50">
                        {/* Elegant grid background overlay */}
                        <div className="absolute inset-0 opacity-[0.03]" style={{ backgroundImage: 'radial-gradient(circle, #000 1px, transparent 1px)', backgroundSize: '8px 8px' }} />
                        
                        {st.status === 'active' ? (
                          <div className="flex flex-col items-center justify-center text-center z-10">
                            {/* Scanning Radar Wave */}
                            <div className="relative flex items-center justify-center w-12 h-12">
                              <span className="absolute inline-flex h-8 w-8 rounded-full bg-blue-400 opacity-20 animate-ping" />
                              <span className="absolute inline-flex h-6 w-6 rounded-full bg-blue-400 opacity-35 animate-pulse" />
                              <Camera className="text-blue-600 relative" size={20} weight="fill" />
                            </div>
                            <span className="text-[10px] text-slate-500 font-bold mt-1 uppercase tracking-wider">Dispositivo en Línea</span>
                          </div>
                        ) : (
                          <div className="flex flex-col items-center justify-center text-center z-10 opacity-60">
                            <Camera className="text-slate-400" size={24} weight="light" />
                            <span className="text-[10px] text-slate-400 font-semibold mt-1 uppercase tracking-wider">Inactivo / Revocado</span>
                          </div>
                        )}
                      </div>

                      {/* Status row and actions */}
                      <div className="flex justify-between items-center pt-3 border-t border-slate-100">
                        <span className="inline-flex items-center text-xs font-bold">
                          <span className={`w-2 h-2 rounded-full mr-1.5 ${
                            st.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'
                          }`} />
                          {st.status === 'active'
                            ? 'Activa'
                            : st.status === 'revoked'
                            ? 'Revocada'
                            : 'Inactiva'}
                        </span>

                        <div className="row-actions flex gap-1.5 flex-wrap">
                          {st.status === 'active' ? (
                            <>
                              <button
                                className="button button-secondary text-xs px-2.5 py-1.5 rounded-lg border-slate-200 hover:bg-slate-50 text-slate-700 font-bold shadow-sm"
                                onClick={() => generateActivationMutation.mutate(st.id)}
                              >
                                Activar
                              </button>
                              <button
                                className="button button-secondary text-rose-600 border-rose-200 hover:bg-rose-50 text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm"
                                onClick={() => setRevocationStationId(st.id)}
                              >
                                Revocar
                              </button>
                            </>
                          ) : (
                            <span className="text-slate-400 text-xs italic font-medium">Histórica</span>
                          )}
                          <button
                            className={`button text-xs px-2.5 py-1.5 rounded-lg font-bold shadow-sm ${
                              selectedStationId === st.id
                                ? 'button-primary bg-blue-600 hover:bg-blue-700 text-white'
                                : 'button-secondary border-slate-200 hover:bg-slate-50 text-slate-700'
                            }`}
                            onClick={() => setSelectedStationId(st.id)}
                          >
                            Cámaras
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12 text-slate-500 italic">No hay estaciones registradas.</div>
              )}
            </div>

            {/* Selected Station Cameras Sub-panel */}
            {selectedStationId && (
              <div className="glass-panel-light p-6 rounded-2xl space-y-4 border-blue-100 bg-white/70 text-slate-800 shadow-sm animate-fade-in">
                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                  <div>
                    <h3 className="text-base font-bold text-slate-900">Cámaras de la Estación</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                      Dispositivo seleccionado: <span className="font-semibold text-slate-700">{stationsQuery.data?.data.find((s) => s.id === selectedStationId)?.name}</span>
                    </p>
                  </div>
                  <button className="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100/70 px-2.5 py-1 rounded-lg transition-colors" onClick={() => setSelectedStationId(null)}>
                    Cerrar
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {/* Cameras List */}
                  <div className="border border-slate-100 rounded-xl p-4 bg-slate-50/50 flex flex-col justify-between">
                    <div>
                      <strong className="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Listado de Cámaras</strong>
                      {camerasQuery.isLoading ? (
                        <div className="flex items-center justify-center p-8">
                          <SpinnerGap className="spin text-blue-600" size={24} />
                        </div>
                      ) : camerasQuery.error ? (
                        <div className="p-4 text-xs text-rose-600 bg-rose-50 rounded-xl">Error: {(camerasQuery.error as Error).message}</div>
                      ) : camerasQuery.data && camerasQuery.data.length > 0 ? (
                        <div className="space-y-2 max-h-48 overflow-y-auto">
                          {camerasQuery.data.map((cam) => (
                            <div key={cam.id} className="flex justify-between items-center bg-white p-3 rounded-lg border border-slate-100 shadow-sm">
                              <div>
                                <strong className="text-sm font-semibold text-slate-900 block">{cam.label}</strong>
                                <code className="text-[10px] font-mono text-slate-500 bg-slate-50 px-1 py-0.5 rounded border border-slate-100/50 mt-0.5 inline-block">{cam.device_identifier}</code>
                              </div>
                              <span className="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-100 flex items-center">
                                <span className="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse" />
                                Activa
                              </span>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div className="text-center py-8 text-xs text-slate-500 italic">Sin cámaras registradas.</div>
                      )}
                    </div>
                  </div>

                  {/* Add Camera Form */}
                  <div className="border border-slate-100 rounded-xl p-4 bg-white space-y-3 shadow-sm">
                    <strong className="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Registrar Nueva Cámara</strong>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1 cursor-pointer">
                      Etiqueta / Nombre
                      <input
                        type="text"
                        className="glass-input-light mt-1 p-2 rounded-xl text-xs font-normal normal-case tracking-normal shadow-sm"
                        placeholder="Ej. Entrada Principal Izquierda"
                        value={cameraLabel}
                        onChange={(e) => setCameraLabel(e.target.value)}
                        required
                      />
                    </label>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wide flex flex-col gap-1 cursor-pointer">
                      Identificador del Dispositivo
                      <input
                        type="text"
                        className="glass-input-light mt-1 p-2 rounded-xl text-xs font-normal normal-case tracking-normal shadow-sm"
                        placeholder="Ej. /dev/video0 o USB-Cam"
                        value={cameraIdentifier}
                        onChange={(e) => setCameraIdentifier(e.target.value)}
                        required
                      />
                    </label>
                    <button
                      className="button button-primary w-full text-xs py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm transition-all"
                      disabled={!cameraLabel || !cameraIdentifier || createCameraMutation.isPending}
                      onClick={() =>
                        createCameraMutation.mutate({
                          stationId: selectedStationId,
                          label: cameraLabel,
                          identifier: cameraIdentifier
                        })
                      }
                    >
                      Añadir Cámara
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Revoke Station Modal Overlay */}
          {revocationStationId && (
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
              <div className="bg-white border border-slate-100 p-6 rounded-2xl max-w-md w-full shadow-2xl space-y-4 text-slate-800">
                <h3 className="text-lg font-bold text-slate-950 flex items-center gap-2">
                  <Warning className="text-rose-500" size={24} weight="fill" />
                  Revocar Estación de Asistencia
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed">
                  Esta acción revocará la sesión técnica del dispositivo permanentemente. La estación permanecerá inactiva en el historial.
                </p>
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wide cursor-pointer space-y-1.5">
                  Motivo de Revocación
                  <input
                    type="text"
                    className="glass-input-light w-full mt-1 p-3 rounded-xl text-sm font-normal normal-case tracking-normal shadow-sm"
                    placeholder="Ej. Estación comprometida / fuera de servicio"
                    value={stationRevokeReason}
                    onChange={(e) => setStationRevokeReason(e.target.value)}
                    required
                  />
                </label>
                <div className="flex justify-end gap-2 pt-2">
                  <button
                    className="button button-secondary rounded-xl text-slate-600 text-sm px-4 py-2 border-slate-200 hover:bg-slate-50 font-semibold shadow-sm"
                    onClick={() => {
                      setRevocationStationId(null)
                      setStationRevokeReason('')
                    }}
                  >
                    Cancelar
                  </button>
                  <button
                    className="button button-primary bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm px-4 py-2 font-semibold shadow-md shadow-rose-500/20 transition-all"
                    disabled={!stationRevokeReason || revokeStationMutation.isPending}
                    onClick={() =>
                      revokeStationMutation.mutate({
                        id: revocationStationId,
                        reason: stationRevokeReason
                      })
                    }
                  >
                    Revocar Estación
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </section>
  )
}
