import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { expect, it } from 'vitest'
import { App } from '@/app/App'
import { FoundationsPage } from '@/features/home/FoundationsPage'

it('renders the public foundation', () => {
  render(<MemoryRouter><App /></MemoryRouter>)
  expect(screen.getByRole('heading', { name: /jornada escolar/i })).toBeInTheDocument()
})

it('renders accessible operational states', () => {
  render(<FoundationsPage context="Portal humano" />)
  expect(screen.getByRole('status', { name: /cargando/i })).toBeInTheDocument()
  expect(screen.getByRole('alert')).toHaveTextContent(/no se pudo cargar/i)
})
