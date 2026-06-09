import { expect, test, type Page } from '@playwright/test'

const auxiliarUser = {
  id: '019e9eff-b5a8-71b3-9014-dea076d57d40',
  name: 'Auxiliar Prueba',
  email: 'auxiliar@example.test',
  active: true,
  roles: ['auxiliar'],
  permissions: []
}

const parentUser = {
  id: '019e9eff-b5a8-71b3-9014-dea076d57d41',
  name: 'Padre Prueba',
  email: 'padre@example.test',
  active: true,
  roles: ['padre'],
  permissions: []
}

async function mockIncidentsApis(page: Page, userSession = auxiliarUser) {
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: userSession } }))

  await page.route(/\/api\/v1\/incidents(\?|$)/, (route) => {
    if (route.request().method() === 'POST') {
      return route.fulfill({ status: 201, json: { data: { id: 'new-inc' } } })
    }
    return route.fulfill({
      json: {
        data: [
          {
            id: 'inc-123',
            student_id: 'alumno-1',
            incident_type: 'Faltamiento',
            severity: 'medium',
            description: 'Problema en el patio',
            status: 'open',
            occurred_at: '2026-06-08T10:00:00Z',
            created_at: '2026-06-08T10:05:00Z',
            updated_at: '2026-06-08T10:05:00Z'
          }
        ],
        meta: { current_page: 1, last_page: 1, total: 1 }
      }
    })
  })
}

test.describe('Incidents Workflow', () => {
  test('Auxiliar can create an incident', async ({ page }) => {
    await mockIncidentsApis(page, auxiliarUser)
    await page.goto('/admin/incidencias')

    await expect(page.getByRole('heading', { name: 'Cuaderno de Incidencias' })).toBeVisible()

    // Open Modal
    await page.getByRole('button', { name: 'Registrar Incidencia' }).first().click()
    await expect(page.getByRole('heading', { name: 'Nueva Incidencia' })).toBeVisible()

    // Fill Form - since there are no ids on inputs, we can use locators
    await page.locator('input').nth(0).fill('alumno-123')
    await page.locator('input').nth(1).fill('Faltamiento')
    await page.locator('select').selectOption('high')
    await page.locator('textarea').fill('El estudiante generó un problema en el patio principal.')
    
    // Simulate submission
    await page.locator('form').getByRole('button', { name: 'Registrar Incidencia' }).click()
    await expect(page.getByRole('heading', { name: 'Nueva Incidencia' })).not.toBeVisible()
  })

  test('Family can view incidents', async ({ page }) => {
    await mockIncidentsApis(page, parentUser)
    await page.goto('/portal/incidencias')

    await expect(page.getByRole('heading', { name: 'Historial de Incidencias' })).toBeVisible()
    await expect(page.getByText('Faltamiento')).toBeVisible()
    await expect(page.getByText('open')).toBeVisible()
  })
})
