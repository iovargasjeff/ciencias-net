import AxeBuilder from '@axe-core/playwright'
import { expect, test, type Page } from '@playwright/test'

const mockStation = {
  id: '019e9eff-77ab-71b3-9014-dea076d57d40',
  name: 'Estación de Asistencia Kiosko',
  location: 'Entrada Principal Pabellón A',
  mode: 'mixed',
  cameras: []
}

async function mockMediaDevices(page: Page) {
  await page.addInitScript(() => {
    // Mock dummy stream
    const mockTrack = {
      stop: () => {},
      enabled: true,
      readyState: 'live',
    }
    
    const mockStream = new MediaStream()
    Object.defineProperty(mockStream, 'getTracks', {
      value: () => [mockTrack],
      configurable: true,
      writable: true,
    })
    Object.defineProperty(mockStream, 'getVideoTracks', {
      value: () => [mockTrack],
      configurable: true,
      writable: true,
    })
    Object.defineProperty(mockStream, 'getAudioTracks', {
      value: () => [],
      configurable: true,
      writable: true,
    })

    const mockDevices = {
      getUserMedia: async () => mockStream,
      enumerateDevices: async () => [
        { kind: 'videoinput', deviceId: 'mock-cam-1', label: 'Cámara Frontal Principal' },
        { kind: 'videoinput', deviceId: 'mock-cam-2', label: 'Cámara Lateral Externa' },
      ],
      addEventListener: () => {},
      removeEventListener: () => {},
    }

    Object.defineProperty(navigator, 'mediaDevices', {
      value: mockDevices,
      configurable: true,
      writable: true,
    })
  })
}

test.describe('Estación de Asistencia Web - FE-009', () => {
  
  test.beforeEach(async ({ page }) => {
    // Setup general API routes
    await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
    
    await page.route('**/api/v1/station-activations', async (route) => {
      const payload = JSON.parse(route.request().postData() || '{}')
      if (payload.activation_code === 'INVALID') {
        return route.fulfill({
          status: 422,
          contentType: 'application/json',
          body: JSON.stringify({
            error: {
              code: 'validation_error',
              message: 'El código de activación ingresado no es válido o ya venció.',
              fields: { activation_code: ['Código inválido'] }
            }
          })
        })
      }
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { id: mockStation.id } })
      })
    })

    await page.route('**/api/v1/station/session', (route) => {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: mockStation })
      })
    })
  })

  test('debe permitir la activación de la estación y redirigir a captura', async ({ page }) => {
    await page.goto('/estacion/activar')
    
    // Test validation failure
    await page.getByLabel('Nombre del dispositivo').fill('Estación E2E')
    await page.getByLabel('Código de activación').fill('INVALID')
    await page.getByRole('button', { name: 'Activar Dispositivo' }).click()

    await expect(page.getByRole('alert')).toContainText('El código de activación ingresado no es válido')

    // Test success activation
    await page.getByLabel('Código de activación').fill('VALIDCODE')
    await page.getByRole('button', { name: 'Activar Dispositivo' }).click()

    // Redirected to capture
    await expect(page).toHaveURL(/\/estacion\/captura$/)
    await expect(page.getByRole('heading', { name: 'Estación de Asistencia Kiosko' })).toBeVisible()
  })

  test('debe permitir configurar múltiples cámaras y modos', async ({ page }) => {
    page.on('console', msg => console.log('PAGE LOG:', msg.text()))
    page.on('pageerror', err => console.log('PAGE ERROR:', err.stack || err.message))
    await mockMediaDevices(page)
    await page.addInitScript(() => {
      sessionStorage.setItem('cienciasnet.station.context', 'active')
    })

    await page.goto('/estacion/captura')

    // Verify enumerated mock cameras list
    await expect(page.getByText('Cámara Frontal Principal').first()).toBeVisible()
    await expect(page.getByText('Cámara Lateral Externa').first()).toBeVisible()

    // Verify first camera starts active and has mode selector
    await expect(page.getByLabel('Activar Cámara Frontal Principal')).toBeChecked()
    await expect(page.getByLabel('Activar Cámara Lateral Externa')).not.toBeChecked()

    // Activate the second camera (PC multiple camera simulation)
    await page.getByLabel('Activar Cámara Lateral Externa').check()
    await expect(page.getByLabel('Activar Cámara Lateral Externa')).toBeChecked()

    // Change camera modes
    await page.getByLabel('Modo de Cámara Frontal Principal').selectOption('entry')
    await page.getByLabel('Modo de Cámara Lateral Externa').selectOption('exit')

    // Grid feed labels should reflect selection
    await expect(page.getByText('INGRESO').first()).toBeVisible()
    await expect(page.getByText('SALIDA').first()).toBeVisible()
  })

  test('debe procesar flujos de éxito, revisión y rechazo', async ({ page }) => {
    await mockMediaDevices(page)
    await page.addInitScript(() => {
      sessionStorage.setItem('cienciasnet.station.context', 'active')
    })

    // Prepare dynamic routes for captures
    let captureOutcome: 'accepted' | 'review' | 'rejected' = 'accepted'
    
    await page.route('**/api/v1/station/captures', async (route) => {
      // Check for Idempotency-Key
      const headers = route.request().headers()
      expect(headers['idempotency-key']).toBeDefined()

      if (captureOutcome === 'accepted') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: '019e9eff-capture-uuid-1',
              outcome: 'accepted',
              score: 0.96,
              student_name: 'Carlos Mendoza Ramos',
              occurred_at: new Date().toISOString()
            }
          })
        })
      } else if (captureOutcome === 'review') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: '019e9eff-capture-uuid-2',
              outcome: 'review',
              score: 0.76,
              occurred_at: new Date().toISOString()
            }
          })
        })
      } else {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              id: '019e9eff-capture-uuid-3',
              outcome: 'rejected',
              score: 0.42,
              occurred_at: new Date().toISOString()
            }
          })
        })
      }
    })

    await page.goto('/estacion/captura')

    // Scenario 1: Success outcome (accepted)
    captureOutcome = 'accepted'
    await page.getByRole('button', { name: 'Registrar Asistencia' }).first().click()
    await expect(page.getByText('¡Asistencia Registrada!')).toBeVisible()
    await expect(page.getByText('Carlos Mendoza Ramos')).toBeVisible()

    // Wait for auto-reset overlay (resets in 3 seconds)
    await expect(page.getByText('¡Asistencia Registrada!')).not.toBeVisible({ timeout: 5000 })

    // Scenario 2: Review outcome
    captureOutcome = 'review'
    await page.getByRole('button', { name: 'Registrar Asistencia' }).first().click()
    await expect(page.getByText('Asistencia en Revisión')).toBeVisible()
    await expect(page.getByText('Tu registro facial está siendo validado')).toBeVisible()

    // Wait for auto-reset overlay (resets in 4 seconds)
    await expect(page.getByText('Asistencia en Revisión')).not.toBeVisible({ timeout: 6000 })

    // Scenario 3: Rejected outcome
    captureOutcome = 'rejected'
    await page.getByRole('button', { name: 'Registrar Asistencia' }).first().click()
    await expect(page.getByText('Asistencia Reclamada').or(page.getByText('Asistencia Rechazada'))).toBeVisible()
    await expect(page.getByText('Rostro no Reconocido')).toBeVisible()
  })

  test('debe manejar timeout de captura (5s)', async ({ page }) => {
    await mockMediaDevices(page)
    await page.addInitScript(() => {
      sessionStorage.setItem('cienciasnet.station.context', 'active')
    })

    await page.route('**/api/v1/station/captures', async (route) => {
      // Simulate backend delay longer than 5s
      await new Promise(resolve => setTimeout(resolve, 5500))
      return route.fulfill({ status: 201 })
    })

    await page.goto('/estacion/captura')
    await page.getByRole('button', { name: 'Registrar Asistencia' }).first().click()

    // Loading overlay shows up
    await expect(page.getByText('Procesando Identificación')).toBeVisible()
    // Resolves to timeout error overlay
    await expect(page.getByText('Tiempo de Espera Agotado')).toBeVisible({ timeout: 6000 })
  })

  test('debe asegurar que no se puede escapar del contexto técnico al portal humano', async ({ page }) => {
    await mockMediaDevices(page)
    await page.addInitScript(() => {
      sessionStorage.setItem('cienciasnet.station.context', 'active')
    })

    // Try directly navigating to a human portal route
    await page.goto('/portal')
    // Guard pushes user right back to station capture
    await expect(page).toHaveURL(/\/estacion\/captura$/)

    // Try going to admin page
    await page.goto('/admin')
    await expect(page).toHaveURL(/\/estacion\/captura$/)

    // Clicking Deactivate cleans context
    await page.getByRole('button', { name: 'Desactivar' }).click()
    await expect(page).toHaveURL(/\/estacion\/activar$/)
    expect(await page.evaluate(() => sessionStorage.getItem('cienciasnet.station.context'))).toBeNull()
  })

  test('no debe tener fallos graves de accesibilidad en vistas de estación', async ({ page }) => {
    await mockMediaDevices(page)
    await page.addInitScript(() => {
      sessionStorage.setItem('cienciasnet.station.context', 'active')
    })

    await page.goto('/estacion/captura')
    const results = await new AxeBuilder({ page }).analyze()
    expect(results.violations.filter(v => ['critical', 'serious'].includes(v.impact ?? ''))).toEqual([])
  })
})
