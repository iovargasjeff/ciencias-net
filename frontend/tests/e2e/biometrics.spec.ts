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

const mockConsentActive = {
  id: 'consent-active-uuid',
  student_id: '019e9eff-student-active-uuid',
  student_name: 'Ana Alumna',
  legal_basis: 'Documentación firmada 2026-06-08',
  status: 'active',
}

const mockConsentRevoked = {
  id: 'consent-revoked-uuid',
  student_id: '019e9eff-student-revoked-uuid',
  student_name: 'Luis Alumno',
  legal_basis: 'Consentimiento anterior',
  status: 'revoked',
}

const mockStationActive = {
  id: 'station-active-uuid',
  name: 'Puerta Principal A',
  location: 'Pabellón Primaria',
  mode: 'mixed',
  active: true,
  status: 'active'
}

const mockStudents = [
  { id: mockConsentActive.student_id, user_id: 'user-active-uuid', dni: '70000001', name: mockConsentActive.student_name },
  { id: mockConsentRevoked.student_id, user_id: 'user-revoked-uuid', dni: '70000002', name: mockConsentRevoked.student_name },
  { id: '019e9eff-new-student-uuid', user_id: 'user-new-uuid', dni: '70000003', name: 'Alumno Nuevo' }
]

interface MockConsent {
  id: string
  student_id: string
  student_name: string
  legal_basis: string
  status: string
}

interface MockStation {
  id: string
  name: string
  location: string
  mode: string
  active: boolean
  status: string
}

interface MockCamera {
  id: string
  label: string
  device_identifier: string
  active: boolean
}

async function mockBiometricsApis(page: Page, user = superadmin) {
  let consents: MockConsent[] = [mockConsentActive, mockConsentRevoked]
  let stations: MockStation[] = [mockStationActive]
  const cameras: MockCamera[] = []

  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', route => route.fulfill({ json: { data: user } }))
  await page.route('**/api/v1/search/students**', async (route) => {
    const search = new URL(route.request().url()).searchParams.get('search')?.toLowerCase() ?? ''
    const students = mockStudents.filter((student) =>
      student.dni.includes(search) || student.name.toLowerCase().includes(search)
    )
    return route.fulfill({ json: { data: students } })
  })

  // Consents API
  await page.route('**/api/v1/biometric-consents', async (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ json: { data: consents } })
    }
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newConsent: MockConsent = {
        id: `consent-new-${Date.now()}`,
        student_id: payload.student_id,
        student_name: 'Alumno Nuevo',
        legal_basis: payload.legal_basis,
        status: 'active'
      }
      consents.push(newConsent)
      return route.fulfill({ status: 201, json: { data: newConsent } })
    }
  })

  await page.route('**/api/v1/biometric-consents/*/revocation', async (route) => {
    const url = route.request().url()
    const consentId = url.split('/').slice(-2)[0]
    consents = consents.map(c => c.id === consentId ? { ...c, status: 'revoked' } : c)
    return route.fulfill({ status: 200, json: { data: { id: consentId, status: 'revoked' } } })
  })

  // Enrollment API
  await page.route('**/api/v1/biometric-enrollments', async (route) => {
    return route.fulfill({ status: 201, json: { data: { id: 'enrollment-success-uuid' } } })
  })

  // Stations API
  await page.route('**/api/v1/stations', async (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ json: { data: stations } })
    }
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newStation: MockStation = {
        id: `station-new-${Date.now()}`,
        name: payload.name,
        location: payload.location,
        mode: payload.mode,
        active: true,
        status: 'active'
      }
      stations.push(newStation)
      return route.fulfill({ status: 201, json: { data: newStation } })
    }
  })

  await page.route('**/api/v1/stations/*/revocation', async (route) => {
    const url = route.request().url()
    const stationId = url.split('/').slice(-2)[0]
    stations = stations.map(s => s.id === stationId ? { ...s, active: false, status: 'revoked' } : s)
    return route.fulfill({ status: 200, json: { data: { id: stationId, status: 'revoked' } } })
  })

  // Cameras API
  await page.route('**/api/v1/stations/*/cameras', async (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ json: { data: cameras } })
    }
    if (route.request().method() === 'POST') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const newCamera = {
        id: `camera-new-${Date.now()}`,
        label: payload.label,
        device_identifier: payload.device_identifier,
        active: true
      }
      cameras.push(newCamera)
      return route.fulfill({ status: 201, json: { data: newCamera } })
    }
  })

  // Activation Code API
  await page.route('**/api/v1/stations/*/activation-codes', async (route) => {
    return route.fulfill({
      status: 201,
      json: {
        data: {
          id: 'code-uuid',
          activation_code: 'ACT-429-110',
          expires_at: new Date(Date.now() + 600000).toISOString()
        }
      }
    })
  })
}

async function selectStudent(page: Page, search: string, studentName: string) {
  await page.getByLabel('Alumno por DNI o nombre').fill(search)
  await page.getByRole('button', { name: 'Buscar' }).evaluate((button: HTMLButtonElement) => button.click())
  const result = page.getByRole('button', { name: new RegExp(studentName) })
  await expect(result).toBeVisible()
  await result.evaluate((button: HTMLButtonElement) => button.click())
}

test.describe('Administración Biométrica y de Dispositivos - FE-009A', () => {

  test('debe restringir acceso si el usuario no tiene permisos', async ({ page }) => {
    const customUser = { ...superadmin, roles: ['docente'], permissions: [] }
    await mockBiometricsApis(page, customUser)
    await page.goto('/admin/biometria')
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('debe permitir otorgar y revocar consentimiento biométrico', async ({ page }) => {
    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')

    // Verify Tab is loaded
    await expect(page.getByRole('heading', { name: 'Biometría y Dispositivos' })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Otorgar Consentimiento' })).toBeVisible()

    // Grant new consent
    await selectStudent(page, '70000003', 'Alumno Nuevo')
    await page.getByLabel('Base Legal / Documentación').fill('Consentimiento firmado por madre')
    await page.getByRole('checkbox').check()
    await page.getByRole('button', { name: 'Registrar Consentimiento' }).click()

    await expect(page.getByText('Consentimiento biométrico otorgado con éxito.')).toBeVisible()

    // Revoke existing active consent
    await page.getByRole('button', { name: 'Revocar' }).first().click({ force: true })
    await page.getByLabel('Motivo de Revocación').fill('Solicitado por mudanza')
    await page.getByRole('button', { name: 'Confirmar Revocación' }).click({ force: true })

    // Status chip should update to revoked
    await expect(page.getByText('Revocado').first()).toBeVisible()
  })

  test('debe bloquear enrolamiento si no hay consentimiento activo', async ({ page }) => {
    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')
    await page.getByRole('button', { name: 'Enrolamiento Facial' }).click({ force: true })

    // Verify Tab loading
    await expect(page.getByRole('heading', { name: 'Verificar Consentimiento' })).toBeVisible()

    // Enter a student ID without consent (revoked student)
    await selectStudent(page, '70000002', 'Luis Alumno')
    await expect(page.getByText('Enrolamiento Bloqueado')).toBeVisible()

    // Verify camera option is locked (camera container shows placeholder)
    await expect(page.getByText('Selecciona un alumno con consentimiento activo')).toBeVisible()
  })

  test('debe permitir enrolar perfil facial con fotos cuando existe consentimiento', async ({ page }) => {
    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')
    await page.getByRole('button', { name: 'Enrolamiento Facial' }).click({ force: true })

    // Enter student ID with active consent
    await selectStudent(page, '70000001', 'Ana Alumna')
    await expect(page.getByText('Consentimiento Activo Encontrado')).toBeVisible()
    await expect(page.getByText('Enrolamiento Bloqueado')).not.toBeVisible()

    // Select files fallback for E2E using locator setInputFiles
    await page.locator('input[type="file"]').setInputFiles([
      { name: 'photo1.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo1') },
      { name: 'photo2.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo2') },
      { name: 'photo3.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo3') }
    ])

    await expect(page.getByText('Fotos Capturadas (3 de 5)')).toBeVisible()

    // Finalize enrollment
    await page.getByRole('button', { name: 'Finalizar y Registrar Enrolamiento' }).click({ force: true })
    await expect(page.getByText('Perfil facial enrolado con éxito.')).toBeVisible()
  })

  test('debe permitir crear, activar, administrar y revocar estaciones', async ({ page }) => {
    page.on('pageerror', err => console.error('PAGE ERROR:', err))
    page.on('console', msg => console.log('PAGE CONSOLE:', msg.text()))

    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')
    await page.getByRole('button', { name: 'Estaciones y Cámaras' }).click({ force: true })

    // Register station
    await page.getByLabel('Nombre').fill('Entrada Lateral C')
    await page.getByLabel('Ubicación').fill('Pabellón Secundaria')
    await page.getByLabel('Modo de Operación').selectOption('entry')
    await page.getByRole('button', { name: 'Registrar Estación' }).click({ force: true })

    await expect(page.getByText('Estación de asistencia registrada.')).toBeVisible()

    // Generate activation code
    await page.getByRole('button', { name: 'Activar' }).first().click({ force: true })
    await expect(page.getByText('ACT-429-110')).toBeVisible()

    // Manage cameras sub-panel
    const camBtn = page.getByRole('button', { name: 'Cámaras', exact: true }).first()
    await camBtn.scrollIntoViewIfNeeded()
    await camBtn.click({ force: true })

    const labelInput = page.getByLabel('Etiqueta / Nombre')
    await labelInput.scrollIntoViewIfNeeded()
    await labelInput.fill('Cámara Lateral C1')

    const identInput = page.getByLabel('Identificador del Dispositivo')
    await identInput.scrollIntoViewIfNeeded()
    await identInput.fill('USB-Cam-99')

    const addCamBtn = page.getByRole('button', { name: 'Añadir Cámara' })
    await addCamBtn.scrollIntoViewIfNeeded()
    await addCamBtn.click({ force: true })

    await expect(page.getByText('Cámara Lateral C1')).toBeVisible()

    // Revoke station
    const revokeBtn = page.getByRole('button', { name: 'Revocar' }).first()
    await revokeBtn.scrollIntoViewIfNeeded()
    await revokeBtn.click({ force: true })

    const reasonInput = page.getByLabel('Motivo de Revocación')
    await reasonInput.scrollIntoViewIfNeeded()
    await reasonInput.fill('Dispositivo dañado')

    const confirmRevokeBtn = page.getByRole('button', { name: 'Revocar Estación' })
    await confirmRevokeBtn.scrollIntoViewIfNeeded()
    await confirmRevokeBtn.click({ force: true })

    // Status chip should update to revoked but station must remain visible
    await expect(page.getByText('Revocada').first()).toBeVisible()
  })

  test('no debe filtrar ni guardar URLs/embeddings biométricos en la consola ni en almacenamiento local', async ({ page }) => {
    const consoleLogs: string[] = []
    page.on('console', msg => consoleLogs.push(msg.text()))

    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')
    await page.getByRole('button', { name: 'Enrolamiento Facial' }).click({ force: true })
    await selectStudent(page, '70000001', 'Ana Alumna')

    await page.locator('input[type="file"]').setInputFiles([
      { name: 'photo1.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo1') },
      { name: 'photo2.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo2') },
      { name: 'photo3.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('photo3') }
    ])

    await page.getByRole('button', { name: 'Finalizar y Registrar Enrolamiento' }).click({ force: true })

    // Assert no raw embeddings or image buffers logged to console
    const leakedData = consoleLogs.filter(log => /data:image|embedding|blob:/i.test(log))
    expect(leakedData).toEqual([])

    // Assert no biometric variables in localStorage or sessionStorage
    const storageKeys = await page.evaluate(() => {
      return [...Object.keys(localStorage), ...Object.keys(sessionStorage)]
    })
    expect(storageKeys.filter(key => /biometric|image|embedding/i.test(key))).toEqual([])
  })

  test('no debe tener fallos graves de accesibilidad en panel de biometría', async ({ page }) => {
    await mockBiometricsApis(page)
    await page.goto('/admin/biometria')
    const results = await new AxeBuilder({ page }).analyze()
    expect(results.violations.filter(v => ['critical', 'serious'].includes(v.impact ?? ''))).toEqual([])
  })
})
