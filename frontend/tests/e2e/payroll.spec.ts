import AxeBuilder from '@axe-core/playwright'
import { expect, test, type Page } from '@playwright/test'

const superadmin = {
  id: '00000000-0000-4000-8000-000000000001',
  name: 'Admin',
  email: 'admin@example.test',
  active: true,
  roles: ['superadmin'],
  permissions: ['gestionar_planilla', 'cerrar_liquidacion']
}

const docenteUser = {
  id: '00000000-0000-4000-8000-000000000002',
  name: 'Docente Juan',
  email: 'juan@example.test',
  active: true,
  roles: ['docente'],
  permissions: []
}

const adminNoPerms = {
  id: '00000000-0000-4000-8000-000000000003',
  name: 'Admin Pepe',
  email: 'pepe@example.test',
  active: true,
  roles: ['administrativo'],
  permissions: []
}

const mockTeachers = [
  { id: 'teacher-1-uuid', name: 'Dora la Exploradora', email: 'dora@example.test', active: true, roles: ['docente'], permissions: [] },
  { id: 'teacher-2-uuid', name: 'Diego Go', email: 'diego@example.test', active: true, roles: ['docente'], permissions: [] }
]

const mockAttendance = [
  {
    id: 'att-1',
    date: '2026-06-08',
    class_session_id: 'session-1-uuid',
    teacher_id: 'teacher-1-uuid',
    teacher_name: 'Dora la Exploradora',
    status: 'late' as const,
    entry_time: '2026-06-08T08:15:00Z',
    exit_time: '2026-06-08T12:00:00Z',
    hourly_rate: '20.00',
    minutes_late: 30,
    hours_absent: 0,
    substitute_teacher_id: null,
    substitute_teacher_name: null,
    reason: null
  },
  {
    id: 'att-2',
    date: '2026-06-08',
    class_session_id: 'session-2-uuid',
    teacher_id: 'teacher-2-uuid',
    teacher_name: 'Diego Go',
    status: 'absent' as const,
    entry_time: null,
    exit_time: null,
    hourly_rate: '25.00',
    minutes_late: 0,
    hours_absent: 4,
    substitute_teacher_id: null,
    substitute_teacher_name: null,
    reason: null
  }
]

const mockRates = [
  { id: 'rate-1', teacher_id: 'teacher-1-uuid', teacher_name: 'Dora la Exploradora', hourly_rate: '20.00', effective_from: '2026-01-01', effective_until: null },
  { id: 'rate-2', teacher_id: 'teacher-2-uuid', teacher_name: 'Diego Go', hourly_rate: '25.00', effective_from: '2026-01-01', effective_until: null }
]

let mockLiquidations = [
  {
    id: 'liq-open-uuid',
    period_start: '2026-06-01',
    period_end: '2026-06-30',
    status: 'open' as const,
    total_teachers: 2,
    total_discount: 270.0,
    created_at: '2026-06-01T00:00:00Z',
    closed_at: null,
    items: [
      {
        id: 'liq-item-1',
        teacher_id: 'teacher-1-uuid',
        teacher_name: 'Dora la Exploradora',
        regular_hours: 80,
        hours_absent_justified: 3.0,
        hours_absent_unjustified: 0,
        minutes_late: 30,
        hourly_rate: '20.00',
        total_discount: 70.0
      },
      {
        id: 'liq-item-2',
        teacher_id: 'teacher-2-uuid',
        teacher_name: 'Diego Go',
        regular_hours: 60,
        hours_absent_justified: 0,
        hours_absent_unjustified: 4.0,
        minutes_late: 0,
        hourly_rate: '25.00',
        total_discount: 200.0
      }
    ]
  }
]

async function mockPayrollApis(page: Page, userSession = superadmin) {
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: userSession } }))

  // Mock Accounts / Teachers list
  await page.route(/\/api\/v1\/accounts(\?|$)/, (route) => {
    return route.fulfill({ json: { data: mockTeachers } })
  })

  // Mock Teacher Attendance logs
  await page.route(/\/api\/v1\/teacher-attendance(\?|$)/, (route) => {
    return route.fulfill({
      json: {
        data: mockAttendance,
        meta: { current_page: 1, last_page: 1, total: mockAttendance.length }
      }
    })
  })

  // Mock Teacher Rates
  await page.route(/\/api\/v1\/teacher-rates(\?|$)/, (route) => {
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newRate = {
        id: `rate-${Date.now()}`,
        teacher_id: payload.teacher_id,
        teacher_name: 'Docente Nuevo',
        hourly_rate: payload.hourly_rate,
        effective_from: payload.effective_from,
        effective_until: payload.effective_until || null
      }
      return route.fulfill({ status: 201, json: { data: newRate } })
    }
    return route.fulfill({ json: { data: mockRates } })
  })

  // Mock Payroll Liquidations
  await page.route(/\/api\/v1\/payroll-liquidations(\?|$)/, (route) => {
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newLiq = {
        id: `liq-${Date.now()}`,
        period_start: payload.period_start,
        period_end: payload.period_end,
        status: 'open' as const,
        total_teachers: 0,
        total_discount: 0,
        created_at: new Date().toISOString(),
        closed_at: null
      }
      return route.fulfill({ status: 201, json: { data: newLiq } })
    }
    return route.fulfill({ json: { data: mockLiquidations } })
  })

  // Mock Close Payroll Liquidation
  await page.route(/\/api\/v1\/payroll-liquidations\/.*\/closure/, (route) => {
    mockLiquidations = mockLiquidations.map((liq) => ({ ...liq, status: 'closed' as const }))
    return route.fulfill({ status: 200, json: { data: { id: 'liq-open-uuid' } } })
  })

  // Mock adjustments
  await page.route('**/api/v1/teacher-attendance/adjustments', (route) => {
    return route.fulfill({ status: 201, json: { data: { id: 'adj-uuid' } } })
  })

  // Mock Class Session Cancellation
  await page.route(/\/api\/v1\/class-sessions\/.*\/cancellation/, (route) => {
    return route.fulfill({ status: 200, json: { data: { id: 'cancellation-uuid' } } })
  })

  // Mock Class Session Substitute
  await page.route(/\/api\/v1\/class-sessions\/.*\/substitute/, (route) => {
    return route.fulfill({ status: 200, json: { data: { id: 'substitute-uuid' } } })
  })

  // Mock generate report
  await page.route('**/api/v1/teacher-attendance/reports', (route) => {
    return route.fulfill({ status: 202, json: { data: { message: 'Report processing' } } })
  })
}

test.describe('Asistencia Docente y Planilla - FE-011', () => {
  test('debe restringir acceso si el usuario no tiene rol ni permiso correcto', async ({ page }) => {
    await mockPayrollApis(page, docenteUser)
    await page.goto('/admin/planilla')
    await expect(page.getByText('Sin permiso')).toBeVisible()

    await mockPayrollApis(page, adminNoPerms)
    await page.goto('/admin/planilla')
    await expect(page.getByText('Acceso Denegado')).toBeVisible()
  })

  test('debe permitir a superadmin visualizar planilla, configurar tarifas e interactuar', async ({ page }) => {
    await mockPayrollApis(page, superadmin)
    await page.goto('/admin/planilla')

    await expect(page.getByRole('heading', { name: 'Asistencia Docente y Planilla' })).toBeVisible()
    await expect(page.getByText('Dora la Exploradora').first()).toBeVisible()
    await expect(page.getByText('Diego Go').first()).toBeVisible()
  })

  test('debe requerir un motivo de al menos 3 caracteres para corregir tardanzas', async ({ page }) => {
    await mockPayrollApis(page, superadmin)
    await page.goto('/admin/planilla')

    // Click "Corregir" button
    await page.getByRole('button', { name: 'Corregir' }).first().click({ force: true })

    // Reason input
    const reasonInput = page.getByPlaceholder('Ej. Docente presentó justificante médico de 3 horas...')
    const confirmButton = page.getByRole('button', { name: 'Confirmar Acción' })

    // Try typing 2 characters
    await reasonInput.fill('ok')
    await expect(confirmButton).toBeDisabled()

    // Type valid motive
    await reasonInput.fill('Motivo válido de prueba')
    await expect(confirmButton).toBeEnabled()

    await confirmButton.click({ force: true })
    await expect(page.getByRole('dialog')).not.toBeVisible()
  })

  test('debe mostrar la fórmula matemática explícita en el desglose de liquidación', async ({ page }) => {
    await mockPayrollApis(page, superadmin)
    await page.goto('/admin/planilla')

    // Go to Tab 3
    await page.getByRole('button', { name: 'Liquidación de Planilla' }).click({ force: true })

    // Inspect period
    await page.getByRole('button', { name: 'Revisar liquidación' }).first().click({ force: true })

    // Verify detailed inspector modal opens
    await expect(page.getByRole('heading', { name: 'Liquidación del Periodo' })).toBeVisible()

    // Click "Formula" for Dora la Exploradora
    await page.getByRole('button', { name: 'Formula' }).first().click({ force: true })

    // Verify math formulas are visible
    await expect(page.getByText('Fórmula: (minutos_tardanza / 60) × tarifa_hora')).toBeVisible()
    await expect(page.getByText('Fórmula: horas_no_laboradas × tarifa_hora', { exact: true })).toBeVisible()
    await expect(page.getByText('Total Descuento Acumulado:')).toBeVisible()
    await expect(page.getByText('(30 min / 60) × S/ 20.00 = S/ 10.00')).toBeVisible()
  })

  test('el cierre de planilla debe requerir confirmación y bloquear la UI', async ({ page }) => {
    await mockPayrollApis(page, superadmin)
    await page.goto('/admin/planilla')

    // Go to Tab 3
    await page.getByRole('button', { name: 'Liquidación de Planilla' }).click({ force: true })

    // Click "Revisar liquidación"
    await page.getByRole('button', { name: 'Revisar liquidación' }).first().click({ force: true })

    const closeBtn = page.getByRole('button', { name: 'Cerrar Planilla' })
    
    // By default, the Close Payroll button should be disabled because the review checkbox isn't checked
    await expect(closeBtn).toBeDisabled()

    // Check confirm checkbox
    await page.getByRole('checkbox').check()
    await expect(closeBtn).toBeEnabled()

    // Intercept closure and complete it
    await closeBtn.click({ force: true })

    // Modal state shows locked banner
    await expect(page.getByText('Planilla Congelada (Solo Lectura)')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Cerrar Planilla' })).not.toBeVisible()

    // Close view
    await page.getByRole('button', { name: 'Cerrar Vista' }).click({ force: true })

    // Go to tab 1 (Asistencia Docente) and check that action buttons are locked
    await page.getByRole('button', { name: 'Asistencia Docente' }).click({ force: true })

    // Dora's entry has a date '2026-06-08' which falls inside the now closed liquidation period '2026-06-01' to '2026-06-30'
    // Therefore the action buttons "Corregir", "Sustituto", "Cancelar clase" should not be visible.
    // Instead it must render the "Bloqueado (Cerrado)" label.
    await expect(page.getByText('Bloqueado (Cerrado)').first()).toBeVisible()
    await expect(page.getByRole('button', { name: 'Corregir' })).not.toBeVisible()
  })

  test('debe cumplir con estándares de accesibilidad WCAG AA', async ({ page }) => {
    await mockPayrollApis(page, superadmin)
    await page.goto('/admin/planilla')

    const results = await new AxeBuilder({ page }).analyze()
    expect(results.violations).toEqual([])
  })
})
