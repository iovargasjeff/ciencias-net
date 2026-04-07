# Módulos del Sistema — CienciasNET

Detalle funcional de cada módulo con sus casos de uso.

---

## Módulo 1 — Auth

- `LoginUser` — Validar credenciales y emitir token Sanctum
- `LogoutUser` — Revocar token activo en PostgreSQL
- `RecoverPassword` — Enviar enlace de recuperación por correo
- `ResetPassword` — Cambiar contraseña con token de recuperación
- `ChangePassword` — Cambiar contraseña desde perfil
- `GetAuthenticatedUser` — Retornar datos del usuario y permisos

---

## Módulo 2 — Alumnos

- `CrearAlumno` — Registrar alumno con ficha completa
- `ActualizarFichaAlumno` — Editar datos personales y académicos
- `SubirFotoAlumno` — Subir imagen, optimizar a WebP y guardar en VPS
- `VincularConPadre` — Asociar alumno a padre/apoderado (solo admin)
- `DesvincularPadre` — Remover vinculación
- `ObtenerPerfilAlumno` — Ficha completa con control de acceso por rol
- `ListarAlumnos` — Filtros: grupo, ciclo, estado, búsqueda por nombre
- `CambiarEstadoMatricula` — Activar / retirar / suspender matrícula

---

## Módulo 3 — Notas

**Tipos de evaluación:**

| Tipo | Descripción | Frecuencia |
|---|---|---|
| `fast_test` | Prueba corta por sesión | Diaria |
| `semanal` | Examen por curso | Semanal |
| `simulacro` | Examen global tipo admisión | Quincenal/mensual |

- `CrearEvaluacion` — Crear evaluación para un grupo/curso/fecha
- `RegistrarNota` — Docente ingresa nota de un alumno
- `RegistrarNotasGrupo` — Ingreso masivo de todo el grupo
- `ActualizarNota` — Corregir nota con registro en audit_logs
- `ObtenerNotasAlumno` — Notas con filtros (período, curso, tipo)
- `CalcularPromedioCurso` — Promedio en un curso
- `CalcularPromedioGlobal` — Promedio general del alumno
- `ObtenerRankingGrupo` — Posición del alumno en su grupo
- `GenerarBoletaPDF` — Exportar boleta de notas en PDF

---

## Módulo 4 — Asistencia

**Estados:** `presente` · `tardanza` · `falta_justificada` · `falta_injustificada`

- `RegistrarAsistenciaGrupo` — Docente pasa lista en una sesión
- `ActualizarAsistencia` — Corregir estado de un alumno
- `JustificarFalta` — Coordinador cambia falta a justificada
- `ObtenerAsistenciaAlumno` — Historial del alumno
- `CalcularPorcentajeAsistencia` — % de presencia en el mes/ciclo
- `VerificarUmbralFaltas` — Disparar alerta si supera N faltas
- `GenerarReporteAsistencia` — Exportar reporte mensual PDF/Excel

---

## Módulo 5 — Portal del Padre

- `ObtenerDashboardPadre` — Promedio actual, asistencia del mes, último simulacro
- `VerNotasHijo` — Notas completas del alumno vinculado
- `VerAsistenciaHijo` — Historial de asistencia
- `VerPagosHijo` — Estado de cuenta y deudas
- `VerComunicados` — Avisos dirigidos a padres
- `ObtenerAlertas` — Alertas de bajo rendimiento o inasistencias

**Regla crítica:** Un padre solo puede ver datos del alumno al que está vinculado. La vinculación es gestionada únicamente por el administrador.

---

## Módulo 6 — Pagos

- `CrearConceptoPago` — Registrar deuda (matrícula, mensualidad, etc.)
- `RegistrarPago` — Anotar pago o abono parcial
- `SubirComprobante` — Subir comprobante escaneado al VPS (PDF/imagen)
- `ObtenerEstadoCuentaAlumno` — Total, pagado y saldo pendiente
- `GenerarReciboPDF` — Exportar recibo de pago
- `ListarMorosos` — Alumnos con pagos vencidos
- `EnviarRecordatorioPago` — Correo automático al padre con deuda
- `GenerarReporteCajaDia` — Ingresos del día para dirección

---

## Módulo 7 — Materiales

- `SubirMaterial` — Docente sube PDF o imagen; se guarda en VPS (`/separatas/`)
- `RegistrarEnlaceExterno` — Registrar URL de YouTube u otro recurso externo
- `ListarMaterialPorCurso` — Alumno ve recursos de su curso por semana
- `DescargarMaterial` — Acceso con validación de matrícula activa
- `ActualizarMaterial` — Editar título, semana o reemplazar archivo
- `EliminarMaterial` — Docente o coordinador elimina un recurso

---

## Módulo 8 — Comunicados

- `PublicarComunicado` — Admin/director redacta y publica aviso
- `SegmentarDestinatarios` — A todos, por rol, por grupo o individual
- `NotificarPorCorreo` — Email automático al publicar aviso importante
- `ListarComunicados` — Ver activos según el rol del usuario
- `MarcarLeido` — Registro de lectura por usuario
- `ArchivarComunicado` — Ocultar comunicado antiguo sin eliminar

---

## Módulo 9 — Horarios y Calendario

- `CrearHorario` — Registrar horario semanal (día, hora, curso, docente, aula)
- `ObtenerHorarioAlumno` — Horario del grupo del alumno
- `ObtenerHorarioDocente` — Clases asignadas al docente
- `CrearEventoCalendario` — Evaluación, simulacro o evento especial
- `ObtenerCalendarioMes` — Vista mensual
- `RegistrarDiaNoLaboral` — Feriado o día sin clases

---

## Módulo 10 — Panel del Docente

- `ObtenerMisGrupos` — Grupos y cursos asignados
- `ObtenerAlumnosDeGrupo` — Lista de alumnos de una sección
- `RegistrarNotasSesion` — Fast test del día
- `VerRendimientoGrupo` — Estadísticas de notas de su sección
- `SubirMaterialCurso` — Subir recurso para su curso
- `EnviarObservacion` — Nota interna para coordinación sobre un alumno

---

## Módulo 11 — Reportes

- `RankingAlumnos` — Ranking por promedio general
- `RendimientoPorCurso` — Promedio por curso y docente
- `EstadisticasAsistencia` — Tasa de asistencia global por grupo y mes
- `HistorialIngresantes` — Alumnos que ingresaron a universidades
- `ReporteFinanciero` — Ingresos, pagados y pendientes del mes
- `ExportarExcel` — Cualquier reporte como .xlsx
- `ExportarPDF` — Cualquier reporte como PDF
