# CienciasNET — Guía de Reunión para Definición de Funcionalidades

**Proyecto:** Sistema Intranet Académico — Academia CIENCIAS
**Repositorio:** `iovargasjeff/CienciasNET`
**Modelo de entrega:** Pago único + mantenimiento mensual
**Preparado por:** Equipo de Desarrollo CienciasNET

---

## Propósito

Guiar la reunión de levantamiento de requerimientos con el equipo directivo de Academia CIENCIAS. Al terminar se debe tener:

- ✅ Lista definitiva de módulos para la versión inicial
- ✅ Acuerdo sobre roles y quién ve qué
- ✅ Flujo de matrícula y pagos definido
- ✅ Presupuesto y fechas acordadas

---

## Descripción del Sistema

**CienciasNET** es un portal web privado accesible desde cualquier navegador (computadora o celular), sin instalar nada. Los archivos (fotos, separatas, comprobantes) se guardan en el propio servidor con optimización automática de imágenes.

**Usuarios del sistema:**

| Rol | Quién es |
|---|---|
| Alumno | Estudiante matriculado |
| Padre / Apoderado | Padre o tutor vinculado al alumno |
| Docente | Profesor asignado a un curso |
| Coordinador | Supervisión académica |
| Administrador | Secretaría, caja, gestión de usuarios |
| Director | Acceso total y estadísticas |

---

## MÓDULO 1 — Autenticación

- [ ] Login con correo y contraseña
- [ ] Recuperación de contraseña por correo
- [ ] Bloqueo tras 5 intentos fallidos
- [ ] Cierre de sesión automático por inactividad
- [ ] Cambio de contraseña desde el perfil

> **Preguntas:**
> - ¿Los alumnos tienen correo propio o usan el correo de sus padres?
> - ¿Las cuentas las crea el administrador o el alumno se puede autoregistrar?

---

## MÓDULO 2 — Gestión de Alumnos

- [ ] Ficha: nombre, DNI, fecha de nacimiento, foto
- [ ] Universidad y carrera objetivo
- [ ] Puntaje de ingreso histórico de referencia
- [ ] Vinculación alumno-padre (solo admin)
- [ ] Estado: activo, retirado, suspendido
- [ ] Búsqueda y filtrado por grupo, ciclo, nombre

> **Preguntas:**
> - ¿Cuántos alumnos tienen actualmente? ¿Cuántos por ciclo?
> - ¿Un alumno puede estar en dos grupos simultáneamente?
> - ¿Cómo están organizados: por grupos, salones, turnos?
> - ¿Llevan registro de alumnos de ciclos anteriores?

---

## MÓDULO 3 — Notas y Evaluaciones

Tipos de evaluación propuestos:

| Tipo | Descripción |
|---|---|
| Fast Test | Prueba corta por sesión (diaria) |
| Evaluación Semanal | Examen por curso |
| Simulacro | Examen global tipo admisión |

- [ ] Registro de notas por docente
- [ ] Ingreso masivo para todo el grupo
- [ ] Promedio automático por curso y global
- [ ] Ranking del alumno en su grupo
- [ ] Vista del alumno: sus propias notas
- [ ] Vista del padre: notas del hijo
- [ ] Edición de nota con auditoría (quién, cuándo)
- [ ] Boleta de notas exportable en PDF

> **Preguntas:**
> - ¿Qué tipos de evaluaciones manejan actualmente y con qué nombres?
> - ¿Hay ponderación entre tipos? (ej: simulacro vale 60%)
> - ¿El docente ingresa las notas directamente o las pasa a secretaría?
> - ¿Actualmente cómo registran las notas? (Excel, papel, otro)

---

## MÓDULO 4 — Asistencia

Estados: Presente · Tardanza · Falta justificada · Falta injustificada

- [ ] Docente pasa lista por sesión
- [ ] Historial de asistencia del alumno
- [ ] Historial visible para el padre
- [ ] Alerta automática al padre si supera N faltas
- [ ] Porcentaje de asistencia en el mes
- [ ] Reporte mensual exportable PDF/Excel

> **Preguntas:**
> - ¿Actualmente cómo pasan lista? (papel, Excel, app)
> - ¿Hay número máximo de faltas antes de una acción?
> - ¿Las tardanzas cuentan como media falta?
> - ¿Quién justifica una falta: coordinador, padre o docente?

---

## MÓDULO 5 — Portal del Padre

- [ ] Dashboard: promedio, asistencia, último simulacro
- [ ] Notas en tiempo real por curso
- [ ] Historial de asistencia del hijo
- [ ] Estado de pagos y deudas
- [ ] Comunicados de la academia
- [ ] Alertas de bajo rendimiento

> **Preguntas:**
> - ¿Actualmente cómo informan a los padres? (WhatsApp, papel, reunión)
> - ¿Hay alumnos sin padre registrado (de provincia, viven solos)?

---

## MÓDULO 6 — Pagos

- [ ] Conceptos: matrícula, mensualidad, material, simulacro externo
- [ ] Estado de cuenta: total, pagado, saldo
- [ ] Registro de pagos y abonos parciales
- [ ] Recibo de pago en PDF
- [ ] Alerta de deuda al padre al ingresar al portal
- [ ] Lista de alumnos morosos
- [ ] Reporte de caja del día para dirección
- [ ] Subir comprobante escaneado (guardado en el VPS)

> **Preguntas:**
> - ¿Cuánto cuesta la mensualidad? ¿Varía por turno?
> - ¿Aceptan Yape, Plin, transferencia, efectivo?
> - ¿Emiten comprobantes actualmente? ¿Recibo simple o boleta SUNAT?
> - ¿El sistema debe bloquear acceso si hay deuda?

---

## MÓDULO 7 — Material de Estudio

- [ ] Docente sube PDFs (guardados en el VPS)
- [ ] Docente registra enlace externo (YouTube, Drive)
- [ ] Organizado por curso, semana y ciclo
- [ ] Solo alumnos matriculados ven el material
- [ ] El docente puede actualizar o eliminar

> **Preguntas:**
> - ¿Distribuyen materiales actualmente? ¿En qué formato?
> - ¿El material es el mismo para todos los grupos?
> - ¿Tienen videos propios o enlazan a YouTube?

---

## MÓDULO 8 — Comunicados

- [ ] Dirección publica avisos en el portal
- [ ] Dirigir a: todos, por rol, por grupo
- [ ] Notificación por correo electrónico
- [ ] El usuario marca el aviso como leído
- [ ] Historial de comunicados

> **Preguntas:**
> - ¿Quién tiene permiso de publicar comunicados?
> - ¿Actualmente por qué medio se comunican con padres?

---

## MÓDULO 9 — Horarios y Calendario

- [ ] Horario semanal por grupo (día, hora, curso, docente, aula)
- [ ] Vista de horario para alumno y docente
- [ ] Calendario de evaluaciones y simulacros
- [ ] Registro de feriados y días sin clases

> **Preguntas:**
> - ¿Los horarios cambian frecuentemente o son fijos por ciclo?
> - ¿Cuántos grupos/turnos tienen?
> - ¿Hay clases los sábados?

---

## MÓDULO 10 — Panel del Docente

- [ ] Ver mis grupos y alumnos asignados
- [ ] Registrar asistencia de la sesión
- [ ] Ingresar notas de evaluaciones
- [ ] Ver rendimiento general de su sección
- [ ] Subir material de estudio de su curso

> **Preguntas:**
> - ¿Los docentes tienen correo electrónico propio?
> - ¿Un docente puede ver las notas de otro docente?
> - ¿Los docentes tienen internet disponible en el aula?

---

## MÓDULO 11 — Reportes y Estadísticas

- [ ] Ranking de alumnos por promedio
- [ ] Rendimiento por curso y docente
- [ ] Estadísticas de asistencia global
- [ ] Historial de ingresantes a universidades por ciclo
- [ ] Reporte financiero mensual
- [ ] Exportar a Excel y PDF

> **Preguntas:**
> - ¿Llevan estadísticas de ingresantes a universidades?
> - ¿Hay métricas que hoy no pueden ver y les gustaría tener?

---

## Priorización Sugerida

### Versión 1.0 — Entrega inicial

| Módulo | Estado |
|---|---|
| 1. Auth y roles | ⬜ Por confirmar |
| 2. Gestión de alumnos | ⬜ Por confirmar |
| 3. Notas y evaluaciones | ⬜ Por confirmar |
| 4. Asistencia | ⬜ Por confirmar |
| 5. Portal del padre | ⬜ Por confirmar |
| 6. Pagos básicos | ⬜ Por confirmar |
| 7. Panel del docente | ⬜ Por confirmar |
| 8. Comunicados | ⬜ Por confirmar |

### Versión 1.5 — Segunda entrega

| Módulo | Estado |
|---|---|
| 7. Material de estudio | ⬜ Por confirmar |
| 9. Horarios y calendario | ⬜ Por confirmar |
| 11. Reportes Excel/PDF | ⬜ Por confirmar |

---

## Acuerdos a Cerrar Hoy

| # | Acuerdo | Resultado |
|---|---|---|
| 1 | Lista definitiva de módulos para v1.0 | |
| 2 | Número de alumnos, docentes y padres actuales | |
| 3 | Fecha estimada de inicio y entrega de v1.0 | |
| 4 | Costo total del proyecto | |
| 5 | Costo mensual de mantenimiento | |
| 6 | Dominio de la academia | |
| 7 | Persona de contacto técnico en la academia | |
| 8 | Acceso a datos actuales para migración | |

---

## Propuesta Económica

| Concepto | Monto |
|---|---|
| Desarrollo completo (pago único) | S/. 5,000 – S/. 8,000 |
| Capacitación + migración de datos | Incluido |
| **Mantenimiento mensual** | **S/. 300 – S/. 450/mes** |
| Hosting, dominio, SSL, backups diarios | Incluido en mantenimiento |
| Soporte WhatsApp y correo (Lun–Vie) | Incluido en mantenimiento |

*Precio final se define luego de confirmar los módulos de esta reunión.*

---

*Documento preparado por el Equipo CienciasNET. Versión sujeta a cambios luego de la reunión.*
