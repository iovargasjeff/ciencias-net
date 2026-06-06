import { expect, test, type Page } from '@playwright/test'

const user = {
  id: '019e9eff-b5a8-71b3-9014-dea076d57d40',
  name: 'Usuario Prueba',
  email: 'usuario@example.test',
  roles: ['administrativo'],
}

async function mockAuth(page: Page) {
  let authenticated = false

  await page.route('**/sanctum/csrf-cookie', route => route.fulfill({ status: 204 }))
  await page.route('**/api/v1/auth/session', route => route.fulfill({
    status: authenticated ? 200 : 401,
    contentType: 'application/json',
    body: JSON.stringify(authenticated ? { data: user } : { error: { code: 'unauthenticated', message: 'Debes iniciar sesión.', fields: {} } }),
  }))
  await page.route('**/api/v1/auth/login', route => {
    authenticated = true
    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: user }) })
  })
  await page.route('**/api/v1/auth/logout', route => {
    authenticated = false
    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: { logged_out: true } }) })
  })
  await page.route('**/api/v1/auth/forgot-password', route => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ data: { message: 'Si el correo está registrado, enviaremos instrucciones de recuperación.' } }),
  }))
}

test('logs in and out with cookies without storing tokens', async ({ page }) => {
  await mockAuth(page)
  await page.goto('/login')
  await page.getByLabel('Correo').fill(user.email)
  await page.getByLabel('Contraseña').fill('correct-password')
  await page.getByRole('button', { name: 'Ingresar' }).click()

  await expect(page).toHaveURL(/\/seleccionar-contexto$/)
  await expect(page.getByRole('heading', { name: 'Elige tu contexto' })).toBeVisible()
  expect(await page.evaluate(() => Object.keys(localStorage).filter(key => /token|auth/i.test(key)))).toEqual([])

  await page.getByRole('button', { name: 'Salir' }).click()
  await expect(page).toHaveURL(/\/login$/)
})

test('shows generic recovery feedback', async ({ page }) => {
  await mockAuth(page)
  await page.goto('/recuperar-contrasena')
  await page.getByLabel('Correo').fill('unknown@example.test')
  await page.getByRole('button', { name: 'Enviar instrucciones' }).click()
  await expect(page.getByRole('status')).toContainText('Si el correo está registrado')
})

test('shows a CSRF error without exposing details', async ({ page }) => {
  await mockAuth(page)
  await page.route('**/api/v1/auth/login', route => route.fulfill({
    status: 419,
    contentType: 'application/json',
    body: JSON.stringify({ error: { code: 'csrf_token_mismatch', message: 'La sesión de seguridad expiró.', fields: {} } }),
  }))
  await page.goto('/login')
  await page.getByLabel('Correo').fill(user.email)
  await page.getByLabel('Contraseña').fill('correct-password')
  await page.getByRole('button', { name: 'Ingresar' }).click()
  await expect(page.getByRole('alert')).toContainText('La sesión de seguridad expiró')
})

test('blocks a station context from the human portal', async ({ page }) => {
  await mockAuth(page)
  await page.addInitScript(() => sessionStorage.setItem('cienciasnet.station.context', 'active'))
  await page.goto('/portal')
  await expect(page).toHaveURL(/\/estacion\/captura$/)
  await expect(page.getByRole('heading', { name: 'Estación de asistencia' })).toBeVisible()
})
