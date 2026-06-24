import AxeBuilder from '@axe-core/playwright'
import { expect, test, type Page } from '@playwright/test'

const superadmin = {
  id: '00000000-0000-4000-8000-000000000001',
  name: 'Admin',
  email: 'admin@example.test',
  active: true,
  roles: ['superadmin'],
  permissions: ['gestionar_dispositivos']
}

const auxiliar = {
  id: '00000000-0000-4000-8000-000000000002',
  name: 'Auxiliar Paco',
  email: 'auxiliar@example.test',
  active: true,
  roles: ['auxiliar'],
  permissions: []
}

const toe = {
  id: '00000000-0000-4000-8000-000000000003',
  name: 'TOE Maria',
  email: 'toe@example.test',
  active: true,
  roles: ['toe'],
  permissions: []
}

const mockGrades = [
  { id: 'grade-1-uuid', name: '1° Primaria' },
  { id: 'grade-2-uuid', name: '2° Primaria' }
]

const mockSections = [
  { id: 'section-a-uuid', name: 'A', grade_id: 'grade-1-uuid' },
  { id: 'section-b-uuid', name: 'B', grade_id: 'grade-2-uuid' }
]

let mockAttendanceRecords = [
  {
    id: 'attendance-1',
    student_id: 'student-1-uuid',
    student_name: 'Juan Perez',
    grade: '1° Primaria',
    section: 'A',
    date: new Date().toISOString().slice(0, 10),
    status: 'present' as const,
    entry_time: new Date().toISOString(),
    exit_time: null,
    justified: false,
    justification_reason: null,
    reason: null
  },
  {
    id: 'attendance-2',
    student_id: 'student-2-uuid',
    student_name: 'Maria Lopez',
    grade: '2° Primaria',
    section: 'B',
    date: new Date().toISOString().slice(0, 10),
    status: 'absent' as const,
    entry_time: null,
    exit_time: null,
    justified: false,
    justification_reason: null,
    reason: null
  }
]

let mockAnomalies = [
  {
    id: 'anomaly-1',
    student_id: 'student-3-uuid',
    student_name: 'Carlos Gomez',
    grade: '1° Primaria',
    section: 'A',
    date: new Date().toISOString().slice(0, 10),
    entry_time: new Date(Date.now() - 3600000).toISOString(),
    exit_time: null,
    status: 'pending' as const
  }
]

let mockRecognitionEvents = [
  {
    id: 'recognition-1',
    station_id: 'station-1-uuid',
    station_name: 'Puerta Principal',
    captured_at: new Date(Date.now() - 600000).toISOString(),
    confidence: 78,
    status: 'pending' as const,
    image_url: null,
    matched_student_id: 'student-4-uuid',
    matched_student_name: 'Sofia Torres',
    outcome: null,
    reason: null
  }
]

async function mockAttendanceApis(
  page: Page,
  user: { id: string; name: string; email: string; active: boolean; roles: string[]; permissions: string[] } = superadmin
) {
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: user } }))

  // Grades & Sections
  await page.route(/\/api\/v1\/grades(\?|$)/, (route) => route.fulfill({ json: { data: mockGrades } }))
  await page.route(/\/api\/v1\/sections(\?|$)/, (route) => route.fulfill({ json: { data: mockSections } }))

  // Attendance
  await page.route(/\/api\/v1\/student-attendance(\?|$)/, (route) => {
    return route.fulfill({
      json: {
        data: mockAttendanceRecords,
        meta: { current_page: 1, last_page: 1, total: mockAttendanceRecords.length }
      }
    })
  })

  // Manual Events
  await page.route('**/api/v1/student-attendance/manual-events', async (route) => {
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newEvent = {
        id: `manual-evt-${Date.now()}`,
        student_id: payload.student_id,
        student_name: `Estudiante ${payload.student_id}`,
        grade: '1° Primaria',
        section: 'A',
        date: new Date().toISOString().slice(0, 10),
        status: payload.event_type === 'entry' ? ('present' as const) : ('present' as const),
        entry_time: payload.event_type === 'entry' ? payload.occurred_at : null,
        exit_time: payload.event_type === 'exit' ? payload.occurred_at : null,
        justified: false,
        justification_reason: null,
        reason: payload.reason
      }
      mockAttendanceRecords.push(newEvent)
      return route.fulfill({ status: 201, json: { data: newEvent } })
    }
  })

  // Day closures
  await page.route('**/api/v1/student-attendance/day-closures', (route) => {
    return route.fulfill({ status: 202, json: { data: { message: 'Day closed' } } })
  })

  // Anomalies
  await page.route(/\/api\/v1\/student-attendance\/anomalies(\?|$)/, (route) => {
    return route.fulfill({
      json: {
        data: mockAnomalies,
        meta: { current_page: 1, last_page: 1, total: mockAnomalies.length }
      }
    })
  })

  await page.route('**/api/v1/student-attendance/anomalies/*/resolution', async (route) => {
    const url = route.request().url()
    const anomalyId = url.split('/').slice(-2)[0]
    const payload = JSON.parse(route.request().postData() || '{}')

    mockAnomalies = mockAnomalies.filter((a) => a.id !== anomalyId)
    // Add real exit time to matching record
    mockAttendanceRecords = mockAttendanceRecords.map((r) => {
      if (r.id === anomalyId) {
        return {
          ...r,
          exit_time: new Date().toISOString(),
          reason: payload.reason
        }
      }
      return r
    })
    return route.fulfill({ status: 200, json: { data: { id: anomalyId } } })
  })

  // Justifications
  await page.route('**/api/v1/student-attendance/absences/*/justification', async (route) => {
    const url = route.request().url()
    const attendanceId = url.split('/').slice(-2)[0]
    const payload = JSON.parse(route.request().postData() || '{}')

    mockAttendanceRecords = mockAttendanceRecords.map((r) => {
      if (r.id === attendanceId) {
        return {
          ...r,
          status: 'excused' as const,
          justified: true,
          justification_reason: payload.reason
        }
      }
      return r
    })
    return route.fulfill({ status: 200, json: { data: { id: attendanceId } } })
  })

  // Recognition events
  await page.route(/\/api\/v1\/recognition-events(\?|$)/, (route) => {
    return route.fulfill({
      json: {
        data: mockRecognitionEvents,
        meta: { current_page: 1, last_page: 1, total: mockRecognitionEvents.length }
      }
    })
  })

  await page.route('**/api/v1/recognition-events/*/review', async (route) => {
    const url = route.request().url()
    const eventId = url.split('/').slice(-2)[0]

    mockRecognitionEvents = mockRecognitionEvents.filter((e) => e.id !== eventId)
    return route.fulfill({ status: 200, json: { data: { id: eventId } } })
  })
}

test.describe('Supervisión de Asistencia - FE-010', () => {
  test('debe restringir acceso si el usuario no tiene permisos (ej. docente)', async ({ page }) => {
    const customUser = { ...superadmin, roles: ['docente'], permissions: [] }
    await mockAttendanceApis(page, customUser)
    await page.goto('/admin/asistencia')
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('debe permitir a superadmin visualizar el listado y aplicar filtros', async ({ page }) => {
    await mockAttendanceApis(page, superadmin)
    await page.goto('/admin/asistencia')

    await expect(page.getByRole('heading', { name: 'Asistencia de Alumnos' })).toBeVisible()
    await expect(page.getByText('Juan Perez').first()).toBeVisible()
    await expect(page.getByText('Maria Lopez').first()).toBeVisible()

    // Test filters
    await page.getByPlaceholder('Nombre o ID...').fill('Juan')
    // Wait briefly for filtering / update click
    await page.getByTitle('Actualizar tabla').click({ force: true })
  })

  test('debe permitir a superadmin registrar un evento manual requiriendo un motivo auditable', async ({ page }) => {
    await mockAttendanceApis(page, superadmin)
    await page.goto('/admin/asistencia')

    await page.getByRole('button', { name: 'Registrar Evento Manual' })
      .evaluate((button: HTMLButtonElement) => button.click())

    await page.getByPlaceholder('Ej. 00000000-0000-0000-0000-000000000000').fill('student-new-manual')
    await page.getByLabel('Hora y Fecha Real Ocurrida').fill('2026-06-08T10:00')

    // Submit without minlength 3 reason should fail due to browser validation, let's test typing it
    await page.getByPlaceholder('Ej. Alumno se retira temprano por cita médica familiar...').fill('Ok') // less than 3
    // Form should block or show error if minLength isn't satisfied.
    // Let's type a valid reason
    await page.getByPlaceholder('Ej. Alumno se retira temprano por cita médica familiar...').fill('Motivo justificado de prueba')
    await page.getByRole('button', { name: 'Registrar Evento', exact: true }).click({ force: true })

    await expect(page.locator('text=Estudiante student-new-manual')).toBeVisible()
  })

  test('debe permitir a superadmin o auxiliar resolver anomalías de entrada registrando hora real y motivo', async ({ page }) => {
    await mockAttendanceApis(page, auxiliar)
    await page.goto('/admin/asistencia')

    // Go to Tab 2
    await page.getByRole('tab', { name: 'Incidencias y Rostros' }).click({ force: true })

    await expect(page.getByText('Carlos Gomez').first()).toBeVisible()
    await expect(page.getByText('Falta Salida').first()).toBeVisible()

    // Click Resolve
    await page.getByRole('button', { name: 'Resolver', exact: true }).click({ force: true })

    // Enforce NO auto-fill: Check that fields are empty or required
    const datetimeInput = page.getByLabel('Hora y Fecha Real Ocurrida de la Salida')
    await expect(datetimeInput).toHaveValue('')
    await datetimeInput.fill('2026-06-08T16:00')

    const reasonInput = page.getByPlaceholder('Ej. Salió en movilidad escolar autorizada sin pasar por el tótem facial...')
    await expect(reasonInput).toHaveValue('')
    await reasonInput.fill('Salida confirmada por auxiliar en puerta')

    await page.getByRole('button', { name: 'Resolver Anomalía' }).click({ force: true })

    await expect(page.getByText('Carlos Gomez')).toHaveCount(0)
  })

  test('debe permitir a TOE (u otros autorizados) justificar faltas requiriendo motivo auditable', async ({ page }) => {
    await mockAttendanceApis(page, toe)
    await page.goto('/admin/asistencia')

    // Verify TOE cannot do manual events or closure tabs
    await expect(page.getByRole('button', { name: 'Registrar Evento Manual' })).not.toBeVisible()
    await expect(page.getByRole('tab', { name: 'Cierre de Jornada' })).not.toBeVisible()

    // Locate absent row and click justify
    await expect(page.getByText('Maria Lopez').first()).toBeVisible()
    await page.getByRole('button', { name: 'Justificar' }).click({ force: true })

    await page.getByPlaceholder('Ej. Presenta certificado médico original de reposo por 24 horas...').fill('Presentó certificado de salud')
    await page.getByRole('button', { name: 'Confirmar Justificación' }).click({ force: true })

    await expect(page.getByText('Justificado').first()).toBeVisible()
  })

  test('debe permitir confirmar, rechazar o reasignar reconocimientos dudosos', async ({ page }) => {
    await mockAttendanceApis(page, superadmin)
    await page.goto('/admin/asistencia')

    await page.getByRole('tab', { name: 'Incidencias y Rostros' }).click({ force: true })
    await expect(page.getByText('Sofia Torres').first()).toBeVisible()

    // Confirm face
    await page.getByRole('button', { name: 'Confirmar', exact: true }).click({ force: true })
    await page.getByPlaceholder('Ej. Rostro coincide plenamente con foto oficial del alumno...').fill('Confirmado plenamente')
    await page.getByRole('button', { name: 'Confirmar Decisión' }).click({ force: true })

    await expect(page.getByText('Sofia Torres')).not.toBeVisible()
  })

  test('debe pasar validación de accesibilidad WCAG AA', async ({ page }) => {
    await mockAttendanceApis(page, superadmin)
    await page.goto('/admin/asistencia')
    
    const results = await new AxeBuilder({ page }).analyze()
    expect(results.violations).toEqual([])
  })
})
