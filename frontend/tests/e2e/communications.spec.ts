import { expect, test, type Page } from '@playwright/test'

const superadmin = {
  id: '00000000-0000-4000-8000-000000000001',
  name: 'Admin',
  email: 'admin@example.test',
  active: true,
  roles: ['superadmin'],
  permissions: []
}

const toeUser = {
  id: 'toe-uuid',
  name: 'TOE Maria',
  email: 'maria.toe@example.test',
  active: true,
  roles: ['toe'],
  permissions: []
}

const padreUser = {
  id: 'padre-uuid',
  name: 'Padre Carlos',
  email: 'carlos@example.test',
  active: true,
  roles: ['padre'],
  permissions: []
}

const alumnoUser = {
  id: 'student-1-uuid',
  name: 'Juan Alumno',
  email: 'juan.al@example.test',
  active: true,
  roles: ['alumno'],
  permissions: []
}

// Mocks Data
const mockGrades = [
  { id: 'grade-1-uuid', name: '1° Secundaria', level: 'secundaria', order: 1, academic_period_id: 'period-2026-uuid' }
]

const mockSections = [
  { id: 'sec-1a-uuid', grade_id: 'grade-1-uuid', name: 'A', capacity: 30 }
]

const mockEnrollments = [
  { id: 'enr-1-uuid', student_id: 'student-1-uuid', section_id: 'sec-1a-uuid', grade_id: 'grade-1-uuid' }
]

const mockLinkedStudents = [
  { id: 'student-1-uuid', name: 'Juan Alumno', relationship: 'padre' }
]

const mockStudentSummary = {
  id: 'student-1-uuid',
  name: 'Juan Alumno',
  biometric_status: 'registered',
  enrollments: [
    { id: 'enr-1-uuid', section: 'A', grade: '1° Secundaria', academic_period: 'Año Escolar 2026' }
  ]
}

let mockAnnouncements = [
  {
    id: 'ann-1-uuid',
    title: 'Simulacro de sismo obligatorio',
    body: 'Este viernes a las 10:00 AM se realizará el simulacro de sismo de carácter obligatorio para toda la comunidad.',
    audience_type: 'all' as const,
    audience_ids: null,
    publish_at: null,
    is_read: false,
    is_archived: false,
    created_at: '2026-06-08T08:00:00Z',
    updated_at: '2026-06-08T08:00:00Z'
  },
  {
    id: 'ann-2-uuid',
    title: 'Reunión de Padres 1° Secundaria A',
    body: 'Se convoca a reunión extraordinaria para coordinar el viaje de estudios de fin de año.',
    audience_type: 'sections' as const,
    audience_ids: ['sec-1a-uuid'],
    publish_at: null,
    is_read: false,
    is_archived: false,
    created_at: '2026-06-08T09:00:00Z',
    updated_at: '2026-06-08T09:00:00Z'
  }
]

const mockNotifications = [
  {
    id: 'not-1-uuid',
    title: 'Boleta de pago emitida',
    body: 'Se ha emitido la boleta correspondiente a la pensión de Junio 2026.',
    is_read: false,
    created_at: '2026-06-09T08:00:00Z'
  }
]

let readCallsCount = 0

async function mockCommsApis(page: Page, userSession = superadmin) {
  // Reset read request counter for idempotency checks
  readCallsCount = 0

  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: userSession } }))

  // Mock Academic
  await page.route('**/api/v1/grades', (route) => route.fulfill({ json: { data: mockGrades } }))
  await page.route('**/api/v1/sections', (route) => route.fulfill({ json: { data: mockSections } }))
  await page.route('**/api/v1/enrollments', (route) => route.fulfill({ json: { data: mockEnrollments } }))

  // Mock Accounts
  await page.route('**/api/v1/accounts**', (route) => {
    const url = new URL(route.request().url())
    const role = url.searchParams.get('role')
    
    let list = [superadmin, toeUser, padreUser, alumnoUser]
    
    // Filter by role if specified in query params
    if (role) {
      list = list.filter(user => user.roles.includes(role))
    }
    
    return route.fulfill({ json: { data: list, meta: { total: list.length } } })
  })

  // Mock Family links
  await page.route('**/api/v1/family/students', (route) => route.fulfill({ json: { data: mockLinkedStudents } }))
  await page.route('**/api/v1/family/students/*/summary', (route) => route.fulfill({ json: { data: mockStudentSummary } }))

  // Mock Announcements lists & creations & read/archive
  await page.route(/\/api\/v1\/announcements(\?|$)/, async (route) => {
    const method = route.request().method()
    const url = new URL(route.request().url())
    
    if (method === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newAnn = {
        id: `ann-new-${Date.now()}`,
        title: payload.title,
        body: payload.body,
        audience_type: payload.audience_type,
        audience_ids: payload.audience_ids || null,
        publish_at: payload.publish_at || null,
        is_read: false,
        is_archived: false,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      mockAnnouncements.push(newAnn)
      return route.fulfill({ status: 201, json: { data: newAnn } })
    }
    
    // Filter announcements based on current user and audience
    let filtered = mockAnnouncements.filter((a) => !a.is_archived)
    
    // If a specific audience filter is applied, respect it
    const audienceType = url.searchParams.get('audience_type')
    if (audienceType && audienceType !== 'all') {
      filtered = filtered.filter(a => a.audience_type === audienceType)
    }
    
    return route.fulfill({ json: { data: filtered } })
  })

  await page.route(/\/api\/v1\/announcements\/([^/]+)\/read/, async (route) => {
    const method = route.request().method()
    if (method === 'PUT') {
      const parts = route.request().url().split('/')
      const id = parts[parts.length - 2]
      const ann = mockAnnouncements.find((a) => a.id === id)
      if (ann) {
        ann.is_read = true
      }
      readCallsCount++
      return route.fulfill({ status: 204 })
    }
    return route.continue()
  })

  await page.route(/\/api\/v1\/announcements\/([^/]+)\/archive/, async (route) => {
    const method = route.request().method()
    if (method === 'PUT') {
      const parts = route.request().url().split('/')
      const id = parts[parts.length - 2]
      const ann = mockAnnouncements.find((a) => a.id === id)
      if (ann) {
        ann.is_archived = true
      }
      return route.fulfill({ status: 204 })
    }
    return route.continue()
  })

  // Mock Notifications list
  await page.route('**/api/v1/notifications', (route) => {
    return route.fulfill({ json: { data: mockNotifications } })
  })
}

test.describe('Comunicaciones y Notificaciones - FE-019', () => {
  test.beforeEach(async () => {
    // Reset the default announcements list
    mockAnnouncements = [
      {
        id: 'ann-1-uuid',
        title: 'Simulacro de sismo obligatorio',
        body: 'Este viernes a las 10:00 AM se realizará el simulacro de sismo de carácter obligatorio para toda la comunidad.',
        audience_type: 'all' as const,
        audience_ids: null,
        publish_at: null,
        is_read: false,
        is_archived: false,
        created_at: '2026-06-08T08:00:00Z',
        updated_at: '2026-06-08T08:00:00Z'
      },
      {
        id: 'ann-2-uuid',
        title: 'Reunión de Padres 1° Secundaria A',
        body: 'Se convoca a reunión extraordinaria para coordinar el viaje de estudios de fin de año.',
        audience_type: 'sections' as const,
        audience_ids: ['sec-1a-uuid'],
        publish_at: null,
        is_read: false,
        is_archived: false,
        created_at: '2026-06-08T09:00:00Z',
        updated_at: '2026-06-08T09:00:00Z'
      }
    ]
  })

  test('debe restringir acceso a gestion de comunicaciones si el usuario no tiene rol publicador', async ({ page }) => {
    await mockCommsApis(page, padreUser)
    await page.goto('/admin/comunicaciones')
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('alcance esperado: previsualiza destinatarios en vivo en el editor segmentado', async ({ page }) => {
    await mockCommsApis(page, toeUser)
    await page.goto('/admin/comunicaciones')

    // Expect preview reach counts all active profiles by default
    await expect(page.locator('#announcement-audience-type')).toHaveValue('all')
    const totalCuentasCount = 4 // superadmin, toe, padre, student
    await expect(page.getByText(`${totalCuentasCount} usuarios`)).toBeVisible()

    // Switch audience segment to Roles
    await page.locator('#announcement-audience-type').selectOption({ label: 'Por Roles específicos' })
    await page.getByLabel('padre').check()

    // Assert reach updates to show only Padre Carlos
    await expect(page.getByText('1 usuarios')).toBeVisible()
    await expect(page.getByText('Padre Carlos')).toBeVisible()
    await expect(page.getByText('TOE Maria')).not.toBeVisible()
  })

  test('muestra error de validacion 422 y mantiene valores cargados', async ({ page }) => {
    await mockCommsApis(page, toeUser)

    // Intercept POST request to yield 422 validation failure
    await page.route('**/api/v1/announcements', (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 422,
          json: {
            error: {
              code: 'validation_error',
              message: 'El contenido ingresado excede las reglas permitidas.',
              fields: {
                body: ['El campo cuerpo debe tener entre 1 y 10000 caracteres.']
              }
            }
          }
        })
      }
      return route.continue()
    })

    await page.goto('/admin/comunicaciones')
    await page.locator('#announcement-title').fill('Comunicado Invalido')
    await page.locator('#announcement-body').fill('Corto')
    await page.getByRole('button', { name: 'Publicar Comunicado' }).click()

    // Error banner should display
    await expect(page.getByText('El contenido ingresado excede las reglas permitidas.')).toBeVisible()

    // Form inputs should retain context
    await expect(page.locator('#announcement-title')).toHaveValue('Comunicado Invalido')
    await expect(page.locator('#announcement-body')).toHaveValue('Corto')
  })

  test('portal de alumno: bandeja de entrada, lectura idempotente y actualizacion de badge', async ({ page }) => {
    await mockCommsApis(page, alumnoUser)
    await page.goto('/portal/comunicaciones')

    // AlumnoUser is enrolled in sec-1a-uuid.
    // Thus, receives 'Simulacro de sismo obligatorio' (all) and 'Reunión de Padres 1° Secundaria A' (sections)
    await expect(page.getByRole('heading', { name: 'Simulacro de sismo obligatorio' })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Reunión de Padres 1° Secundaria A' })).toBeVisible()

    // Check unread count badge in layout (Megaphone link has badge count 2)
    const badge = page.locator('#unread-communications-badge')
    await expect(badge).toHaveText('2')

    // Open first unread announcement details
    await page.getByRole('button', { name: 'Simulacro de sismo obligatorio' }).click()

    // Expect details view to display
    await expect(page.getByText('Este viernes a las 10:00 AM se realizará')).toBeVisible()

    // Check read api put is called once
    await expect.poll(() => readCallsCount).toBe(1)

    // Go back to inbox
    await page.getByRole('button', { name: 'Volver a la bandeja' }).click()

    // Badge count should decrease to 1
    await expect(badge).toHaveText('1')

    // Reopen same announcement to verify idempotence
    await page.getByRole('button', { name: 'Simulacro de sismo obligatorio' }).click()
    await expect(page.getByText('Este viernes a las 10:00 AM se realizará')).toBeVisible()

    // Read counter should still be 1 (idempotency client check)
    expect(readCallsCount).toBe(1)
  })

  test('coordinacion: archivar comunicado publicado remueve de bandeja activa', async ({ page }) => {
    await mockCommsApis(page, superadmin)
    await page.goto('/admin/comunicaciones')

    await page.getByRole('button', { name: 'Historial' }).click()

    // Click trash button next to Simulacro
    page.once('dialog', (dialog) => dialog.accept()) // Mock confirmation dialog
    await page.locator('tbody tr').first().locator('button').click({ force: true })

    // Wait and assert announcement is archived
    await expect(page.locator('tbody tr').filter({ hasText: 'Simulacro de sismo obligatorio' })).not.toBeVisible()
  })
})
