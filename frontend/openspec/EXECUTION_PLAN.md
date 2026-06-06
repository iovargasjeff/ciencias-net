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
| FE-005 | `[ ]` | `add-account-role-administration` | Kiara | Vincenzo | FE-002, FE-003, Backend BE-004 |
| FE-006 | `[ ]` | `add-family-links-administration` | Kiara | Vincenzo | FE-003, Backend BE-005 |
| FE-007 | `[ ]` | `add-academic-structure-administration` | Kiara | Vincenzo | FE-002, FE-003, Backend BE-006 |
| FE-008 | `[ ]` | `add-family-student-portal-shell` | Vincenzo | Kiara | FE-003, Backend BE-005/BE-006/BE-007 |

## Fase 2: Facial y asistencia

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-009 | `[ ]` | `add-web-station-activation-capture` | Vincenzo | Kiara | FE-003, Backend BE-009/BE-010 |
| FE-009A | `[ ]` | `add-biometric-station-administration` | Kiara | Vincenzo | FE-003, Backend BE-007/BE-009 |
| FE-010 | `[ ]` | `add-student-attendance-supervision` | Kiara | Vincenzo | FE-002, FE-003, Backend BE-011 |
| FE-011 | `[ ]` | `add-teacher-attendance-payroll` | Kiara | Vincenzo | FE-003, Backend BE-012/BE-013 |

## Fase 3: Finanzas

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-012 | `[ ]` | `add-finance-configuration-benefits` | Kiara | Vincenzo | FE-003, Backend BE-014 |
| FE-013 | `[ ]` | `add-obligations-payments-administration` | Kiara | Vincenzo | FE-003, Backend BE-015/BE-016 |
| FE-014 | `[ ]` | `add-finance-state-portals` | Vincenzo | Kiara | FE-008, Backend BE-017 |

## Fase 4: Evaluación y contenido

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-015 | `[ ]` | `add-assessment-result-entry` | Vincenzo | Kiara | FE-003, Backend BE-018/BE-019 |
| FE-016 | `[ ]` | `add-result-publication-reports-portals` | Vincenzo | Kiara | FE-008, Backend BE-020 |
| FE-017 | `[ ]` | `add-materials-portal` | Kiara | Vincenzo | FE-002, Backend BE-021 |
| FE-018 | `[ ]` | `add-schedules-calendar-portals` | Kiara | Vincenzo | FE-002, Backend BE-022 |
| FE-019 | `[ ]` | `add-communications-notifications` | Kiara | Vincenzo | FE-002, Backend BE-023 |

## Fase 5: Incidencias y Psicología

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-020 | `[ ]` | `add-incidents-workflow` | Kiara | Vincenzo | FE-003, Backend BE-024 |
| FE-021 | `[ ]` | `add-private-psychology-portal` | Kiara | Vincenzo | FE-003, Backend BE-025 |

## Fase 6: Calidad y release

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| FE-022 | `[ ]` | `harden-accessibility-performance` | Kiara | Vincenzo | FE-005..FE-021 |
| FE-023 | `[ ]` | `verify-frontend-release-e2e` | Vincenzo | Kiara | FE-004, FE-022, Backend BE-028 |

## Regla de ejecución

Cada change se detalla desde sus documentos fuente antes de implementarse. No se inicia un change bloqueado ni se archiva sin demostrar todos sus escenarios.
