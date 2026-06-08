# Leyes Supremas de CienciasNET (Global AI Rules)

> [!IMPORTANT]
> **Instrucción para Agentes de IA:** Debes leer y acatar estas reglas globales antes de ejecutar cualquier comando, modificar archivos o proponer cambios en este repositorio.

Este proyecto es un **Monorepo** que contiene el Backend (Laravel) y el Frontend (React/Vite). Para garantizar la integridad de la arquitectura diseñada por el Arquitecto Principal , debes operar bajo las siguientes directivas absolutas:

## 1. Arquitectura API-First (Design-First)
- **Prohibido adivinar contratos:** Nunca inventes endpoints ni asumas las respuestas del backend. 
- Toda interacción entre frontend y backend debe aprobarse primero en `docs/api/` mediante OpenAPI.
- Durante la primera etapa, Jefferson diseña y aprueba los contratos. Más adelante, los owners backend podrán
  proponerlos, pero Jefferson conserva la aprobación.
- El frontend debe utilizar mocks basados en el contrato aprobado si el backend aún no está listo y cubrir éxito,
  errores, permisos, estados vacíos y latencia.

## 2. Flujo de Trabajo y Ramas (Git Flow)
- **Prohibido tocar la rama `main` directamente.**
- Todo trabajo debe realizarse en una rama de característica nueva: `git checkout -b feature/<ID-DEL-CHANGE>`.
- Al finalizar, se debe generar un commit descriptivo y abrir un Pull Request para la revisión exclusiva del Arquitecto o Reviewers designados. Tras el merge, la rama muere.

## 3. Navegación del Monorepo
Este repositorio tiene reglas técnicas aisladas para cada entorno. Dependiendo de tu tarea asignada, **es obligatorio** que leas las reglas locales antes de escribir código:

- Si vas a modificar la interfaz, lee `frontend/Rules.md` y `frontend/AGENTS.md`. (Presta especial atención a la prohibición estricta de instalar nuevas librerías de UI/tipografías sin permiso explícito).
- Si vas a modificar el servidor o la base de datos, lee `backend/Rules.md` y `backend/AGENTS.md`.

## 4. Vertical Slicing
- Trabaja por módulos de valor completo (Features). Termina todo el espectro de la funcionalidad que te fue asignada en el `EXECUTION_PLAN.md` correspondiente antes de dar el ticket por cerrado.
- Nunca cierres un ticket (marcar con `[x]`) si dependes de Mocks temporales o si quedan pruebas pendientes.

## 5. Fuente de Verdad y Backend Modular

- La carpeta `docs/` es la fuente de verdad superior para producto, dominio, seguridad y arquitectura.
- Si un `openspec/` change, `tasks.md` o instruccion de agente contradice `docs/`, el agente debe detenerse,
  reportar la contradiccion y priorizar `docs/`.
- Antes de implementar backend, leer `docs/architecture/backend.md`, `docs/product/roles-and-permissions.md` y los
  contratos relevantes en `docs/api/`.
- No crear codigo de dominio en `backend/app/Models`, `backend/app/Http/Controllers`, `backend/app/UseCases` ni
  `backend/app/Policies`.
- Todo nuevo feature backend debe vivir bajo `backend/app/Modules/<Modulo>/` siguiendo `Domain`, `Application`,
  `Infrastructure` y `Presentation` solo cuando esas capas aporten valor real.
- Las carpetas raiz legacy existen solo por migracion incremental; no son destino valido para codigo nuevo.
