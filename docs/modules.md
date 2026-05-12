# Módulos del Sistema — CienciasNET (Colegio Ciencias)

Detalle funcional de cada módulo con sus casos de uso y reglas de negocio.

---

## Módulo 1 — Control de Asistencia y Alertas (Alumnos)

**Responsable principal:** Auxiliar de Educación

### Reglas de Negocio

- **Horario límite de entrada:** 7:45 AM. Después de esa hora se registra como tardanza.
- **Notificaciones (Correo):** El sistema envía correo automático al padre al registrar el ingreso (ej. 7:40 AM) y al registrar la salida (ej. 3:30 PM). Todos los asuntos llevan el prefijo `[CienciasNET]`.
- **Regla de Faltas:** Si un alumno acumula 3 faltas, el sistema genera una alerta automática en el panel de TOE y Auxiliar para citar al padre.
- **Justificaciones:** Solo TOE o Auxiliar pueden cambiar el estado de "Falta Injustificada" a "Falta Justificada".

### Casos de Uso

- `RegistrarIngreso` — Auxiliar registra ingreso del alumno (hora y estado: presente/tardanza)
- `RegistrarSalida` — Auxiliar registra salida del alumno
- `ObtenerAsistenciaAlumno` — Historial de asistencia por alumno con filtros (mes, grado)
- `ObtenerAsistenciaGrado` — Lista de asistencia del día por grado y sección
- `JustificarFalta` — TOE o Auxiliar cambia estado a falta justificada
- `VerificarUmbralFaltas` — Detecta alumnos con 3+ faltas y genera alerta
- `ObtenerAlertasFaltas` — Panel de TOE/Auxiliar muestra alumnos con umbral superado
- `EnviarNotificacionIngreso` — Correo automático al padre al registrar ingreso
- `EnviarNotificacionSalida` — Correo automático al padre al registrar salida
- `GenerarReporteAsistencia` — Reporte mensual por alumno/grado

---

## Módulo 2 — Control de Asistencia y Planilla (Docentes)

**Responsable exclusivo:** Yanina (Administrativo)

### Reglas de Negocio

- Los docentes **no registran su propia asistencia** en el sistema. Lo hace Yanina.
- **Tardanzas (Acumulativas):** Los minutos de tardanza de los docentes se acumulan mensualmente. El sistema suma todos los minutos de retraso en el mes para facilitar el descuento salarial.
- **Falta Justificada:** Se descuentan únicamente las horas no laboradas (no se pagan).
- **Falta Injustificada:** Se descuentan las horas no laboradas multiplicadas por 2 (sanción doble).
- **Sustitución:** El sistema permite registrar si las horas fueron cubiertas por un docente sustituto.

### Casos de Uso

- `RegistrarAsistenciaDocente` — Yanina registra ingreso/salida/estado del docente
- `ObtenerAsistenciaDocente` — Historial de asistencia por docente
- `CalcularMinutosTardanzaMes` — Suma de minutos de tardanza en el mes
- `CalcularHorasDescuento` — Cálculo automático de horas a descontar según reglas
- `RegistrarDocenteSustituto` — Asignar docente que cubrió las horas
- `GenerarReportePlanilla` — Reporte mensual para cálculo de descuentos

---

## Módulo 3 — Gestión Financiera y Pagos

**Responsable exclusivo:** Yanina (Administrativo)
**Visualización:** Padres (solo estado de cuenta de sus hijos)

### Estructura de Costos Base

| Concepto | Monto |
|---|---|
| Cuota de Ingreso | S/ 200.00 |
| Matrícula | S/ 480.00 |
| Mensualidad Base | S/ 480.00 |

### Reglas de Negocio

- **Pronto Pago:** Si el padre cancela la mensualidad hasta el último día del mes (30 o 31), el sistema aplica automáticamente un descuento de S/ 30.00, dejando la deuda en S/ 450.00.
- **Condiciones Especiales por Alumno:** `normal` (pago completo), `becado` (1er puesto — no paga o paga fracción), `descuento` (acuerdo con dirección).

### Casos de Uso

- `CrearConceptoPago` — Registrar concepto (Matrícula, Mensualidad, Cuota de Ingreso)
- `RegistrarPago` — Registrar pago o abono parcial de un alumno
- `AplicarDescuentoProntoPago` — Aplica S/ 30 de descuento si paga hasta fin de mes
- `ObtenerEstadoCuenta` — Total requerido, monto pagado, saldo pendiente por alumno
- `ObtenerEstadoCuentaPadre` — Padre ve pagos de sus hijos vinculados
- `ListarMorosos` — Alumnos con pagos vencidos
- `EnviarRecordatorioPago` — Correo automático al padre con deuda pendiente (asunto: `[CienciasNET] Recordatorio de pago`)
- `GenerarReciboPDF` — Exportar recibo de pago en PDF
- `GenerarReporteCaja` — Ingresos del día/mes para dirección

---

## Módulo 4 — Evaluación y Academia

**Responsable principal:** Coordinador Académico

### Reglas de Negocio

- **Ciclo Semanal:** Exámenes se rinden los viernes y se publican el martes siguiente.
- **Estructura de Exámenes:**
  - 1° a 4° de Secundaria: Examen general de 40 preguntas.
  - 5° de Secundaria: Examen tipo admisión de 60 preguntas, dividido por canales (`ciencias` / `letras`).
- **Publicación:** Mientras `publicado = false`, las notas no son visibles para alumnos ni padres.
- **Ranking:** Al publicar el examen, el sistema calcula automáticamente el puesto (`puesto_ranking`) de cada alumno.

### Casos de Uso

- `CrearExamen` — Coordinador crea examen (título, fecha, grado, canal, total preguntas)
- `RegistrarNotas` — Registro de puntajes por alumno y examen
- `RegistrarNotasMasivo` — Carga masiva de notas para todo el grado/sección
- `ActualizarNota` — Corrección de nota con registro en audit_logs
- `PublicarExamen` — Activa `publicado = true` el martes. Calcula rankings.
- `ObtenerNotasAlumno` — Notas por alumno con filtros (bimestre, examen)
- `ObtenerRanking` — Ranking de alumnos por examen o acumulado
- `ObtenerNotasPadre` — Padre ve notas de sus hijos (solo exámenes publicados)
- `GenerarBoletaPDF` — Exportar boleta de notas

---

## Módulo 5 — Cuaderno de Incidencias y Psicología

**Responsables:** Auxiliar (registro inicial) → TOE (derivación) → Psicología (atención confidencial)

### Reglas de Negocio

- **Registro Actitudinal:** El Auxiliar registra incidencias conductuales en el Cuaderno de Incidencias virtual.
- **Derivación:** Casos graves pasan a TOE. TOE puede derivar a Psicología.
- **Notificar a padres:** TOE notifica a los padres sobre faltas graves por correo (asunto: `[CienciasNET] Incidencia conductual — <nombre alumno>`).
- **Confidencialidad:** El módulo de Psicología (`atenciones_psicologia`) es privado. Sus reportes detallados (`notas_privadas`) no son visibles para docentes ni auxiliares. Solo Psicología y Dirección (superadmin).
- **Escalamiento:** TOE puede reportar denuncias a UGEL desde el sistema.

### Casos de Uso

- `RegistrarIncidencia` — Auxiliar registra incidencia (tipo, descripción, alumno)
- `ObtenerIncidenciasAlumno` — Historial de incidencias de un alumno
- `DerivarATOE` — Auxiliar deriva caso grave a TOE
- `DerivarAPsicologia` — TOE deriva caso a Psicología
- `NotificarPadreIncidencia` — TOE envía correo al padre sobre falta grave
- `RegistrarAtencionPsicologia` — Psicóloga registra atención y notas privadas
- `ObtenerAtencionesAlumno` — Psicología y Dirección ven historial confidencial
- `ReportarAUGEL` — TOE genera reporte para denuncia externa
- `GenerarReporteIncidencias` — Reporte semanal para TOE/Auxiliar

---

## Módulo 6 — Materiales de Estudio

### Casos de Uso

- `SubirMaterial` — Coordinador/Docente sube PDF, imagen o recurso (grado y sección destino)
- `RegistrarEnlaceExterno` — Registrar URL de YouTube u otro recurso
- `ListarMaterialPorGrado` — Alumnos ven recursos de su grado/sección por semana
- `DescargarMaterial` — Acceso con validación de matrícula activa
- `ActualizarMaterial` — Editar título, semana o reemplazar archivo
- `EliminarMaterial` — Coordinador elimina recurso

---

## Módulo 7 — Horarios y Calendario

### Casos de Uso

- `CrearHorario` — Registrar horario semanal (grado, sección, día, hora, docente, aula)
- `ObtenerHorarioGrado` — Horario semanal por grado y sección
- `ObtenerHorarioDocente` — Clases asignadas al docente
- `ObtenerHorarioPadre` — Padre ve horario del grado de su hijo
- `CrearEventoCalendario` — Examen, simulacro, evento especial
- `ObtenerCalendarioMes` — Vista mensual de eventos
- `RegistrarDiaNoLaboral` — Feriado o día sin clases

---

## Módulo 8 — Comunicados

### Casos de Uso

- `PublicarComunicado` — Dirección/TOE/Coord. publica aviso
- `SegmentarDestinatarios` — Por rol (padre, docente), por grado, o general
- `NotificarPorCorreo` — Email automático al publicar aviso importante
- `ListarComunicados` — Ver activos según el rol/grado del usuario
- `MarcarLeido` — Registro de lectura por usuario
- `ArchivarComunicado` — Ocultar comunicado antiguo sin eliminar
