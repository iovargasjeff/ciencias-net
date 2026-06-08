# Frontend Execution Plan

## Leyenda

- `[ ]` pendiente
- `[~]` en progreso
- `[-]` bloqueado
- `[x]` terminado, verificado y archivado

## Fase 0: Fundación ejecutable

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-001 | `[x]` | `initialize-vite-frontend-foundation` | Vincenzo | Kiara | Ninguna |
| FE-002 | `[x]` | `add-design-system-layouts` | Kiara | Vincenzo | FE-001 |
| FE-003 | `[x]` | `add-api-client-routing-auth` | Vincenzo | Kiara | FE-001, Backend BE-002/BE-003 |
| FE-004 | `[x]` | `configure-frontend-quality-e2e` | Vincenzo | Kiara | FE-001, FE-002 |

## Fase 1: Identidad y academia

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-005 | `[x]` | `add-account-role-administration` | Kiara | Jefferson | FE-002, FE-003 |
| FE-006 | `[x]` | `add-family-links-administration` | Kiara | Jefferson | FE-003 |
| FE-007 | `[x]` | `add-academic-structure-administration` | Kiara | Jefferson | FE-002, FE-003 |
| FE-008 | `[x]` | `add-family-student-portal-shell` | Kiara | Jefferson | FE-003 |

## Fase 2: Facial y asistencia

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-009 | `[x]` | `add-web-station-activation-capture` | Vincenzo | Jefferson | FE-003 |
| FE-009A | `[x]` | `add-biometric-station-administration` | Vincenzo | Jefferson | FE-003 |
| FE-010 | `[x]` | `add-student-attendance-supervision` | Vincenzo | Jefferson | FE-002, FE-003 |
| FE-011 | `[ ]` | `add-teacher-attendance-payroll` | Vincenzo | Jefferson | FE-003 |

## Fase 3: Finanzas

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-012 | `[ ]` | `add-finance-configuration-benefits` | Kiara | Jefferson | FE-003 |
| FE-013 | `[ ]` | `add-obligations-payments-administration` | Kiara | Jefferson | FE-003 |
| FE-014 | `[ ]` | `add-finance-state-portals` | Kiara | Jefferson | FE-008 |

## Fase 4: Evaluación y contenido

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-015 | `[ ]` | `add-assessment-result-entry` | Vincenzo | Jefferson | FE-003 |
| FE-016 | `[ ]` | `add-result-publication-reports-portals` | Vincenzo | Jefferson | FE-008 |
| FE-017 | `[ ]` | `add-materials-portal` | Vincenzo | Jefferson | FE-002 |
| FE-018 | `[ ]` | `add-schedules-calendar-portals` | Vincenzo | Jefferson | FE-002 |
| FE-019 | `[ ]` | `add-communications-notifications` | Vincenzo | Jefferson | FE-002 |

## Fase 5: Incidencias y Psicología

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-020 | `[ ]` | `add-incidents-workflow` | Kiara | Jefferson | FE-003 |
| FE-021 | `[ ]` | `add-private-psychology-portal` | Kiara | Jefferson | FE-003 |

## Fase 6: Calidad y release

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-022 | `[ ]` | `harden-accessibility-performance` | Vincenzo | Jefferson | FE-005..FE-021 |
| FE-023 | `[ ]` | `verify-frontend-release-e2e` | Vincenzo | Jefferson | FE-004, FE-022 |

## Regla de ejecución

Cada change se detalla desde sus documentos fuente y su fila en `API_CONTRACTS.md` antes de implementarse. No se inicia
un change bloqueado, con contrato requerido sin aprobar, ni se archiva sin demostrar todos sus escenarios.
