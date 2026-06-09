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

let mockMaterials = [
  {
    id: 'mat-1-uuid',
    title: 'Guía de Ecuaciones Lineales',
    description: 'Material de teoría y ejercicios para resolver en clase',
    teaching_assignment_id: 'load-juan-uuid',
    week: 2,
    type: 'file' as const,
    url: null,
    file_id: 'file-1-uuid',
    file_name: 'ecuaciones_lineales.pdf',
    file_size: 1548200, // ~1.5 MB
    created_at: '2026-06-01T08:00:00Z',
    updated_at: '2026-06-01T08:00:00Z'
  },
  {
    id: 'mat-2-uuid',
    title: 'Video Explicativo de Cinemática',
    description: 'Enlace externo para ver el video en YouTube',
    teaching_assignment_id: 'load-pedro-uuid',
    week: 3,
    type: 'link' as const,
    url: 'https://youtube.com/watch?v=cinematica',
    file_id: null,
    file_name: null,
    file_size: null,
    created_at: '2026-06-02T10:00:00Z',
    updated_at: '2026-06-02T10:00:00Z'
  }
]

async function mockMaterialsApis(page: Page, userSession = superadmin) {
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

  // Mock Family links / Linked Students / summaries
  await page.route('**/api/v1/family/students', (route) => route.fulfill({ json: { data: mockLinkedStudents } }))
  await page.route('**/api/v1/family/students/*/summary', (route) => route.fulfill({ json: { data: mockStudentSummary } }))

  // Mock Materials API list, create, edit, delete
  await page.route(/\/api\/v1\/materials(\?|$)/, async (route) => {
    const method = route.request().method()
    if (method === 'POST') {
      // Create File Material
      const newMat = {
        id: `mat-new-${Date.now()}`,
        title: 'Nuevo Material Subido',
        description: 'Descripción del material subido',
        teaching_assignment_id: 'load-juan-uuid',
        week: 4,
        type: 'file' as const,
        url: null,
        file_id: 'file-new-uuid',
        file_name: 'archivo_nuevo.pdf',
        file_size: 204800,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      mockMaterials.push(newMat)
      return route.fulfill({ status: 201, json: { data: newMat } })
    }
    return route.fulfill({ json: { data: mockMaterials } })
  })

  await page.route('**/api/v1/material-links', async (route) => {
    const payload = JSON.parse(route.request().postData() || '{}')
    const newMat = {
      id: `mat-new-link-${Date.now()}`,
      title: payload.title,
      description: payload.description || null,
      teaching_assignment_id: payload.teaching_assignment_id,
      week: payload.week || null,
      type: 'link' as const,
      url: payload.url,
      file_id: null,
      file_name: null,
      file_size: null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
    mockMaterials.push(newMat)
    return route.fulfill({ status: 201, json: { data: newMat } })
  })

  await page.route(/\/api\/v1\/materials\/[^/]+$/, async (route) => {
    const method = route.request().method()
    const url = route.request().url()
    const id = url.substring(url.lastIndexOf('/') + 1)

    if (method === 'PATCH') {
      const payload = JSON.parse(route.request().postData() || '{}')
      const target = mockMaterials.find(m => m.id === id)
      if (target) {
        if (payload.title) target.title = payload.title
        if (payload.description !== undefined) target.description = payload.description
        if (payload.week !== undefined) target.week = payload.week
      }
      return route.fulfill({ status: 200, json: { data: target } })
    }

    if (method === 'DELETE') {
      mockMaterials = mockMaterials.filter(m => m.id !== id)
      return route.fulfill({ status: 200, json: { message: 'Material archivado correctamente' } })
    }

    return route.fulfill({ status: 404 })
  })

  // Replace file
  await page.route(/\/api\/v1\/materials\/[^/]+\/file/, async (route) => {
    const url = route.request().url()
    const id = url.substring(url.indexOf('materials/') + 10, url.indexOf('/file'))
    const target = mockMaterials.find(m => m.id === id)
    if (target) {
      target.file_name = 'archivo_reemplazado.docx'
      target.file_size = 512000
    }
    return route.fulfill({ status: 200, json: { data: target } })
  })

  // Mock download endpoint returning a binary blob
  await page.route(/\/api\/v1\/materials\/.*\/download/, async (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/pdf',
      body: Buffer.from('contenido mock pdf')
    })
  })
}

test.describe('Materiales de Estudio - FE-017', () => {
  test('debe restringir acceso a gestion de materiales si el usuario no tiene rol docente/coordinador', async ({ page }) => {
    await mockMaterialsApis(page, padreUser)
    await page.goto('/admin/materiales')
    await expect(page.getByText('Sin permiso')).toBeVisible()
  })

  test('aislamiento de carga: docente Juan solo ve su curso asignado', async ({ page }) => {
    await mockMaterialsApis(page, docenteJuan)
    await page.goto('/admin/materiales')

    const select = page.locator('#course-select')
    await expect(select).toBeVisible()
    
    // Juan teaches Matemática I but Pedro teaches Física Química V
    await expect(select).toContainText('Matemática I')
    await expect(select).not.toContainText('Física Química V')
  })

  test('frontend validation: archivo superior a 10MB arroja error inmediato sin enviar petición', async ({ page }) => {
    let requestSent = false
    await mockMaterialsApis(page, docenteJuan)
    await page.route('**/api/v1/materials', (route) => {
      requestSent = true
      return route.continue()
    })

    await page.goto('/admin/materiales')
    await page.locator('#course-select').selectOption({ label: 'Matemática I - 1° Secundaria "A"' })
    await page.getByRole('button', { name: 'Nuevo Material' }).click()

    // Title input
    await page.getByPlaceholder('Ej. Guía de Vectores y Cinemática').fill('Examen ultra pesado')

    // Create custom 11MB file in browser to upload
    const filePayload = {
      name: 'heavy.zip',
      mimeType: 'application/zip',
      buffer: Buffer.alloc(11 * 1024 * 1024)
    }

    await page.setInputFiles('input[type="file"]', {
      name: filePayload.name,
      mimeType: filePayload.mimeType,
      buffer: filePayload.buffer
    })

    await page.getByRole('button', { name: 'Publicar' }).click()

    // Should display validation error on size
    await expect(page.getByText('El archivo supera el límite permitido de 10 MB.')).toBeVisible()
    expect(requestSent).toBe(false)
  })

  test('preserva el contexto del formulario si el backend retorna 422', async ({ page }) => {
    await mockMaterialsApis(page, docenteJuan)

    // Intercept POST to return 422 error
    await page.route(/\/api\/v1\/materials(\?|$)/, (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 422,
          json: {
            error: {
              code: 'validation_failed',
              message: 'El servidor determinó que el archivo es corrupto o inválido.',
              fields: { file: ['El archivo no cumple con el formato requerido.'] }
            }
          }
        })
      }
      return route.continue()
    })

    await page.goto('/admin/materiales')
    await page.locator('#course-select').selectOption({ label: 'Matemática I - 1° Secundaria "A"' })
    await page.getByRole('button', { name: 'Nuevo Material' }).click()

    await page.getByPlaceholder('Ej. Guía de Vectores y Cinemática').fill('Clase de Ecuaciones Especiales')
    await page.getByPlaceholder('Instrucciones o notas adicionales para los alumnos...').fill('Por favor leer completo')
    await page.getByPlaceholder('Ej. 1').fill('15')

    await page.setInputFiles('input[type="file"]', {
      name: 'corrupto.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('PDF corrupto')
    })

    await page.getByRole('button', { name: 'Publicar' }).click()

    // 422 error banner should be visible
    await expect(page.getByText('El servidor determinó que el archivo es corrupto o inválido.')).toBeVisible()

    // Fields should still contain the values
    await expect(page.getByPlaceholder('Ej. Guía de Vectores y Cinemática')).toHaveValue('Clase de Ecuaciones Especiales')
    await expect(page.getByPlaceholder('Instrucciones o notas adicionales para los alumnos...')).toHaveValue('Por favor leer completo')
    await expect(page.getByPlaceholder('Ej. 1')).toHaveValue('15')
  })

  test('sube un archivo correctamente mostrando el progreso', async ({ page }) => {
    await mockMaterialsApis(page, docenteJuan)
    await page.goto('/admin/materiales')
    await page.locator('#course-select').selectOption({ label: 'Matemática I - 1° Secundaria "A"' })

    await page.getByRole('button', { name: 'Nuevo Material' }).click()
    await page.getByPlaceholder('Ej. Guía de Vectores y Cinemática').fill('Guía de Ecuaciones Especiales')

    await page.setInputFiles('input[type="file"]', {
      name: 'guia_especial.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('pdf data')
    })

    await page.getByRole('button', { name: 'Publicar' }).click()

    // Dialog should close, and new material should render in the list
    await expect(page.getByRole('heading', { name: 'Nuevo Material Subido' })).toBeVisible()
  })

  test('crea enlace externo con validación de URL y lo publica', async ({ page }) => {
    await mockMaterialsApis(page, docenteJuan)
    await page.goto('/admin/materiales')
    await page.locator('#course-select').selectOption({ label: 'Matemática I - 1° Secundaria "A"' })

    await page.getByRole('button', { name: 'Nuevo Material' }).click()
    await page.getByRole('button', { name: 'Enlace Externo' }).click()

    // Invalid URL validation test
    await page.getByPlaceholder('Ej. Guía de Vectores y Cinemática').fill('Enlace a Khan Academy')
    await page.getByPlaceholder('https://example.com/recurso').fill('www.khanacademy.org')
    await page.getByRole('button', { name: 'Publicar' }).click()

    await expect(page.getByText('El enlace debe ser una URL válida que empiece con http:// o https://')).toBeVisible()

    // Make URL valid
    await page.getByPlaceholder('https://example.com/recurso').fill('https://www.khanacademy.org')
    await page.getByRole('button', { name: 'Publicar' }).click()

    // Verification of publication
    await expect(page.getByRole('heading', { name: 'Enlace a Khan Academy' })).toBeVisible()
  })

  test('edita, reemplaza archivo y archiva un material en el admin', async ({ page }) => {
    await mockMaterialsApis(page, docenteJuan)
    await page.goto('/admin/materiales')
    await page.locator('#course-select').selectOption({ label: 'Matemática I - 1° Secundaria "A"' })

    // 1. Edit
    await page.getByTitle('Editar detalles').first().click()
    await page.getByPlaceholder('Ej. Guía de Vectores y Cinemática').fill('Guía de Ecuaciones Lineales Modificada')
    await page.getByRole('button', { name: 'Guardar Cambios' }).click()
    await expect(page.getByRole('heading', { name: 'Guía de Ecuaciones Lineales Modificada' })).toBeVisible()

    // 2. Replace File
    await page.getByRole('button', { name: 'Reemplazar Archivo' }).first().click()
    await page.setInputFiles('input[type="file"]', {
      name: 'guia_reemplazada.docx',
      mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      buffer: Buffer.from('docx content')
    })
    await page.getByRole('button', { name: 'Reemplazar', exact: true }).click()
    await expect(page.getByText('archivo_reemplazado.docx')).toBeVisible()

    // 3. Archive
    await page.getByTitle('Archivar material').first().click({ force: true })
    await page.getByRole('button', { name: 'Sí, Archivar' }).click()
    await expect(page.getByRole('heading', { name: 'Guía de Ecuaciones Lineales Modificada' })).not.toBeVisible()
  })

  test('portal de alumno: aislamiento por matrícula y filtrado por línea de tiempo', async ({ page }) => {
    await mockMaterialsApis(page, alumnoUser)
    await page.goto('/portal/materiales')

    // Alumno is in 1° Secundaria "A" which is linked to load-juan-uuid (Matemática I)
    // Alumno is NOT in 5° Secundaria "A" which is load-pedro-uuid (Física Química V)
    // Thus, Alumno should see 'Guía de Ecuaciones Lineales' (load-juan-uuid) but NOT 'Video Explicativo de Cinemática' (load-pedro-uuid)
    await expect(page.getByRole('heading', { name: 'Guía de Ecuaciones Lineales' })).toBeVisible()
    await expect(page.getByRole('heading', { name: 'Video Explicativo de Cinemática' })).not.toBeVisible()

    // Timeline selector: Filter by Week 2 (has material)
    await page.locator('select').nth(1).selectOption({ label: 'Semana 2' })
    await expect(page.getByRole('heading', { name: 'Guía de Ecuaciones Lineales' })).toBeVisible()

    // Filter by Week 3 (does not have material for load-juan-uuid)
    await page.locator('select').nth(1).selectOption({ label: 'Semana 3' })
    await expect(page.getByRole('heading', { name: 'Guía de Ecuaciones Lineales' })).not.toBeVisible()
    await expect(page.getByText('No se encontraron materiales')).toBeVisible()
  })
})
