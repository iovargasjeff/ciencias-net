import { expect, test, type Page } from '@playwright/test'

const superadmin = {
  id: '00000000-0000-4000-8000-000000000001',
  name: 'Admin',
  email: 'admin@example.test',
  active: true,
  roles: ['superadmin'],
  permissions: []
}

const docenteJuan = {
  id: 'docente-juan-uuid',
  name: 'Docente Juan',
  email: 'juan@example.test',
  active: true,
  roles: ['docente'],
  permissions: []
}

const docentePedro = {
  id: 'docente-pedro-uuid',
  name: 'Docente Pedro',
  email: 'pedro@example.test',
  active: true,
  roles: ['docente'],
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
const mockAcademicPeriods = [
  { id: 'period-2026-uuid', name: 'Año Escolar 2026', start_date: '2026-03-01', end_date: '2026-12-20', status: 'active' }
]

const mockGrades = [
  { id: 'grade-1-uuid', name: '1° Secundaria', level: 'secundaria', order: 1, academic_period_id: 'period-2026-uuid' },
  { id: 'grade-5-uuid', name: '5° Secundaria', level: 'secundaria', order: 5, academic_period_id: 'period-2026-uuid' }
]

const mockSections = [
  { id: 'sec-1a-uuid', grade_id: 'grade-1-uuid', name: 'A', capacity: 30 },
  { id: 'sec-5a-uuid', grade_id: 'grade-5-uuid', name: 'A', capacity: 30 }
]

const mockCourses = [
  { id: 'course-mat-uuid', code: 'MAT-1', name: 'Matemática I' },
  { id: 'course-fis-uuid', code: 'FIS-5', name: 'Física Química V' }
]

const mockAssignments = [
  // Juan teaches Math to 1° Secundaria A
  { id: 'load-juan-uuid', teacher_id: 'docente-juan-uuid', course_id: 'course-mat-uuid', section_id: 'sec-1a-uuid', academic_period_id: 'period-2026-uuid' },
  // Pedro teaches Physics to 5° Secundaria A
  { id: 'load-pedro-uuid', teacher_id: 'docente-pedro-uuid', course_id: 'course-fis-uuid', section_id: 'sec-5a-uuid', academic_period_id: 'period-2026-uuid' }
]

const mockStudents = [
  { id: 'student-1-uuid', name: 'Juan Alumno', email: 'juan.al@example.test', active: true, roles: ['alumno'], permissions: [] }
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

let mockSchedules = [
  {
    id: 'sched-1-uuid',
    teaching_assignment_id: 'load-juan-uuid',
    weekday: 1, // Lunes
    starts_at: '08:00',
    ends_at: '09:30',
    room: 'Aula 101',
    created_at: '2026-06-01T08:00:00Z',
    updated_at: '2026-06-01T08:00:00Z'
  },
  {
    id: 'sched-2-uuid',
    teaching_assignment_id: 'load-pedro-uuid',
    weekday: 2, // Martes
    starts_at: '10:00',
    ends_at: '11:30',
    room: 'Laboratorio Física',
    created_at: '2026-06-01T08:00:00Z',
    updated_at: '2026-06-01T08:00:00Z'
  }
]

let mockCalendarEvents = [
  {
    id: 'event-1-uuid',
    title: 'Examen de Admisión Simulación',
    starts_at: '2026-06-09T09:00:00Z',
    ends_at: '2026-06-09T13:00:00Z',
    event_type: 'academic' as const,
    description: 'Simulacro general obligatorio para todo secundaria.',
    created_at: '2026-06-01T08:00:00Z',
    updated_at: '2026-06-01T08:00:00Z'
  },
  {
    id: 'event-2-uuid',
    title: 'Feriado de la Batalla de Arica',
    starts_at: '2026-06-07T00:00:00Z',
    ends_at: '2026-06-07T23:59:59Z',
    event_type: 'holiday' as const,
    description: 'Día no laborable para el colegio.',
    created_at: '2026-06-01T08:00:00Z',
    updated_at: '2026-06-01T08:00:00Z'
  }
]

async function mockSchedulesApis(page: Page, userSession = superadmin) {
  page.on('console', msg => console.log('BROWSER LOG:', msg.text()))
  page.on('pageerror', err => console.log('BROWSER ERROR:', err.message))
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: userSession } }))

  // Mock Academic Data
  await page.route('**/api/v1/academic-periods', (route) => route.fulfill({ json: { data: mockAcademicPeriods } }))
  await page.route('**/api/v1/grades', (route) => route.fulfill({ json: { data: mockGrades } }))
  await page.route('**/api/v1/sections', (route) => route.fulfill({ json: { data: mockSections } }))
  await page.route('**/api/v1/courses', (route) => route.fulfill({ json: { data: mockCourses } }))
  await page.route('**/api/v1/teaching-assignments', (route) => route.fulfill({ json: { data: mockAssignments } }))

  // Mock Accounts
  await page.route('**/api/v1/accounts**', (route) => {
    const list = [superadmin, docenteJuan, docentePedro, ...mockStudents]
    return route.fulfill({ json: { data: list } })
  })

  // Mock Family link details
  await page.route('**/api/v1/family/students', (route) => route.fulfill({ json: { data: mockLinkedStudents } }))
  await page.route('**/api/v1/family/students/*/summary', (route) => route.fulfill({ json: { data: mockStudentSummary } }))

  // Mock Schedules lists & creations
  await page.route(/\/api\/v1\/schedules(\?|$)/, async (route) => {
    const method = route.request().method()
    if (method === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newSched = {
        id: `sched-new-${Date.now()}`,
        teaching_assignment_id: payload.teaching_assignment_id,
        weekday: payload.weekday,
        starts_at: payload.starts_at,
        ends_at: payload.ends_at,
        room: payload.room || null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      mockSchedules.push(newSched)
      return route.fulfill({ status: 201, json: { data: newSched } })
    }
    const url = new URL(route.request().url())
    const teacherId = url.searchParams.get('teacher_id')
    const sectionId = url.searchParams.get('section_id')
    let filteredSchedules = [...mockSchedules]
    if (teacherId) {
      const validAssignmentIds = mockAssignments
        .filter(a => a.teacher_id === teacherId)
        .map(a => a.id)
      filteredSchedules = filteredSchedules.filter(s => validAssignmentIds.includes(s.teaching_assignment_id))
    } else if (sectionId) {
      const validAssignmentIds = mockAssignments
        .filter(a => a.section_id === sectionId)
        .map(a => a.id)
      filteredSchedules = filteredSchedules.filter(s => validAssignmentIds.includes(s.teaching_assignment_id))
    }
    return route.fulfill({ json: { data: filteredSchedules } })
  })

  // Mock Calendar events lists & creations
  await page.route(/\/api\/v1\/calendar-events(\?|$)/, async (route) => {
    const method = route.request().method()
    if (method === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newEvt = {
        id: `event-new-${Date.now()}`,
        title: payload.title,
        starts_at: payload.starts_at,
        ends_at: payload.ends_at,
        event_type: payload.event_type,
        description: payload.description || null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      mockCalendarEvents.push(newEvt)
      return route.fulfill({ status: 201, json: { data: newEvt } })
    }
    return route.fulfill({ json: { data: mockCalendarEvents } })
  })
}

test.describe('Horarios y Calendario - FE-018', () => {
  test.beforeEach(async () => {
    mockSchedules = [
      {
        id: 'sched-1-uuid',
        teaching_assignment_id: 'load-juan-uuid',
        weekday: 1, // Lunes
        starts_at: '08:00',
        ends_at: '09:30',
        room: 'Aula 101',
        created_at: '2026-06-01T08:00:00Z',
        updated_at: '2026-06-01T08:00:00Z'
      },
      {
        id: 'sched-2-uuid',
        teaching_assignment_id: 'load-pedro-uuid',
        weekday: 2, // Martes
        starts_at: '10:00',
        ends_at: '11:30',
        room: 'Laboratorio Física',
        created_at: '2026-06-01T08:00:00Z',
        updated_at: '2026-06-01T08:00:00Z'
      }
    ]

    mockCalendarEvents = [
      {
        id: 'event-1-uuid',
        title: 'Examen de Admisión Simulación',
        starts_at: '2026-06-09T09:00:00Z',
        ends_at: '2026-06-09T13:00:00Z',
        event_type: 'academic' as const,
        description: 'Simulacro general obligatorio para todo secundaria.',
        created_at: '2026-06-01T08:00:00Z',
        updated_at: '2026-06-01T08:00:00Z'
      },
      {
        id: 'event-2-uuid',
        title: 'Feriado de la Batalla de Arica',
        starts_at: '2026-06-07T00:00:00Z',
        ends_at: '2026-06-07T23:59:59Z',
        event_type: 'holiday' as const,
        description: 'Día no laborable para el colegio.',
        created_at: '2026-06-01T08:00:00Z',
        updated_at: '2026-06-01T08:00:00Z'
      }
    ]
  })
  test('debe restringir acceso a gestion de horarios si el usuario no tiene rol docente/coordinador', async ({ page }) => {
    await mockSchedulesApis(page, padreUser)
    await page.goto('/admin/horarios')
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('aislamiento de carga: docente Juan solo ve su curso en read-only', async ({ page }) => {
    await mockSchedulesApis(page, docenteJuan)
    await page.goto('/admin/horarios')

    // Juan teaches Matemática I
    await expect(page.getByRole('heading', { name: 'Matemática I' })).toBeVisible()
    
    // Pedro teaches Física Química V; Juan must not see it
    await expect(page.getByRole('heading', { name: 'Física Química V' })).not.toBeVisible()

    // No create schedule button visible
    await expect(page.getByRole('button', { name: 'Programar Clase' })).not.toBeVisible()
  })

  test('muestra error de solapamiento al recibir 409 y preserva datos del formulario', async ({ page }) => {
    await mockSchedulesApis(page, superadmin)

    // Intercept POST to return 409 Conflict
    await page.route('**/api/v1/schedules', (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 409,
          json: {
            error: {
              code: 'schedule_overlap_conflict',
              message: 'El docente Docente Juan ya cuenta con otra clase programada en el horario de 08:00 a 09:30 en el aula Aula 101.',
              fields: {}
            }
          }
        })
      }
      return route.continue()
    })

    await page.goto('/admin/horarios')
    await page.locator('#section-filter-select').selectOption({ label: '1° Secundaria "A"' })

    await page.getByRole('button', { name: 'Programar Clase' }).click({ force: true })

    // Fill schedule form
    await page.locator('#form-assignment-select').selectOption({ label: 'Matemática I (Docente Juan)' })
    await page.getByPlaceholder('Ej. Salón 102').fill('Aula 101')
    await page.getByRole('button', { name: 'Guardar' }).click({ force: true })

    // Expect conflict warning banner to appear
    await expect(page.getByText('El docente Docente Juan ya cuenta con otra clase programada')).toBeVisible()

    // Expect inputs to remain filled (preserving context)
    await expect(page.locator('#form-assignment-select')).toHaveValue('load-juan-uuid')
    await expect(page.getByPlaceholder('Ej. Salón 102')).toHaveValue('Aula 101')
  })

  test('crea horario escolar exitosamente', async ({ page }) => {
    await mockSchedulesApis(page, superadmin)
    await page.goto('/admin/horarios')

    await page.locator('#section-filter-select').selectOption({ label: '1° Secundaria "A"' })
    await page.getByRole('button', { name: 'Programar Clase' }).click({ force: true })

    await page.locator('#form-assignment-select').selectOption({ label: 'Matemática I (Docente Juan)' })
    await page.getByPlaceholder('Ej. Salón 102').fill('Aula 102')
    await page.getByRole('button', { name: 'Guardar' }).click({ force: true })

    // Modal should close and the schedule listing should be updated
    await expect(page.locator('#form-assignment-select')).not.toBeVisible()
  })

  test('crea actividad de calendario correctamente', async ({ page }) => {
    await mockSchedulesApis(page, superadmin)
    await page.goto('/admin/horarios')

    await page.getByRole('button', { name: 'Calendario de Actividades' }).click({ force: true })
    await page.getByRole('button', { name: 'Crear Actividad / Feriado' }).click({ force: true })

    await page.getByPlaceholder('Ej. Feriado del Día del Maestro').fill('Reunión General del Colegio')
    
    // Fill datetime-local
    await page.locator('input[type="datetime-local"]').first().fill('2026-06-15T09:00')
    await page.locator('input[type="datetime-local"]').nth(1).fill('2026-06-15T12:00')
    
    await page.getByRole('button', { name: 'Guardar' }).click({ force: true })

    // Verification
    await expect(page.getByRole('heading', { name: 'Reunión General del Colegio' })).toBeVisible()
  })

  test('portal de alumno: aislamiento por matrícula y exploración de calendario', async ({ page }) => {
    await mockSchedulesApis(page, alumnoUser)
    await page.goto('/portal/horarios')

    // Alumno is enrolled in 1° Secundaria "A" which is linked to load-juan-uuid (Matemática I)
    // Alumno is NOT in 5° Secundaria "A" which is load-pedro-uuid (Física Química V)
    // Thus, Alumno should see 'Matemática I' but NOT 'Física Química V' class
    await expect(page.getByRole('heading', { name: 'Matemática I' })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Física Química V' })).not.toBeVisible()

    // Explore Calendar Escolar tab
    await page.getByRole('button', { name: 'Calendario Escolar' }).click({ force: true })

    // Select June 9th explicitly so the assertion does not depend on the current day.
    await page.getByRole('button', { name: '9', exact: true }).click()
    await expect(page.getByRole('heading', { name: 'Examen de Admisión Simulación' })).toBeVisible()
  })
})
