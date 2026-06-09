import { expect, test, type Page } from '@playwright/test'

const psicologiaUser = {
  id: 'psy-uuid',
  name: 'Psicologo Prueba',
  email: 'psicologia@example.test',
  active: true,
  roles: ['psicologia'],
  permissions: []
}

const auxiliarUser = {
  id: 'aux-uuid',
  name: 'Auxiliar Prueba',
  email: 'auxiliar@example.test',
  active: true,
  roles: ['auxiliar'],
  permissions: []
}

async function mockPsychologyApis(page: Page, userSession = psicologiaUser) {
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', (route) => route.fulfill({ json: { data: userSession } }))

  await page.route(/\/api\/v1\/psychology(\?|$)/, (route) => {
    if (route.request().method() === 'POST') {
      return route.fulfill({ status: 201, json: { data: { id: 'new-psy-care' } } })
    }
    return route.fulfill({
      json: {
        data: [
          {
            id: 'care-123',
            student_id: 'alumno-privado',
            incident_type: 'Entrevista',
            severity: 'high',
            summary: 'Sesión 1 - Seguimiento',
            confidential_notes: 'Notas secretas que nadie mas debe ver',
            status: 'closed',
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

test.describe('Psychology Workflow', () => {
  test('Auxiliar cannot access psychology portal', async ({ page }) => {
    await mockPsychologyApis(page, auxiliarUser)
    await page.goto('/admin/psicologia')

    // Expect to be blocked by permission route
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('Psychologist can view and create confidential care records', async ({ page }) => {
    await mockPsychologyApis(page, psicologiaUser)
    await page.goto('/admin/psicologia')

    await expect(page.getByRole('heading', { name: 'Bandeja Privada de Psicología' })).toBeVisible()
    await expect(page.getByText('Sesión 1 - Seguimiento')).toBeVisible()

    // The confidential notes should NOT be in the summary table!
    await expect(page.getByText('Notas secretas')).not.toBeVisible()

    // Open Form
    await page.getByRole('button', { name: 'Registrar Atención' }).click()
    await expect(page.getByRole('heading', { name: 'Nueva Atención Psicológica' })).toBeVisible()

    // Fill form
    await page.locator('input[type="text"]').nth(0).fill('alumno-123')
    await page.locator('input[type="text"]').nth(1).fill('Entrevista por bajo rendimiento')
    await page.locator('textarea').fill('El alumno indicó problemas familiares...')
    
    // Submit
    await page.locator('form').getByRole('button', { name: 'Registrar Atención' }).click()
    await expect(page.getByRole('heading', { name: 'Nueva Atención Psicológica' })).not.toBeVisible()
  })
})
