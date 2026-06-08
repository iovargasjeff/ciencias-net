import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'

test('loads without console errors and has no serious accessibility violations', async ({ page }) => {
  const errors: string[] = []
  await page.route('**/api/v1/auth/session', route => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ data: null }),
  }))
  page.on('console', message => {
    if (message.type() === 'error') errors.push(message.text())
  })

  await page.goto('/')
  await expect(page.getByRole('heading', { name: /jornada escolar/i })).toBeVisible()

  const results = await new AxeBuilder({ page }).analyze()
  expect(results.violations.filter(item => ['serious', 'critical'].includes(item.impact ?? ''))).toEqual([])
  expect(errors).toEqual([])
})

test('protects the human portal and keeps station context separate', async ({ page }) => {
  await page.route('**/api/v1/auth/session', route => route.fulfill({
    status: 401,
    contentType: 'application/json',
    body: JSON.stringify({ error: { code: 'unauthenticated', message: 'Debes iniciar sesión.', fields: {} } }),
  }))
  await page.goto('/portal')
  await expect(page).toHaveURL(/\/login$/)

  await page.goto('/estacion')
  await expect(page.getByText('Sesión técnica limitada')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Activar Estación' })).toBeVisible()
})
