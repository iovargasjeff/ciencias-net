# Seguridad — CienciasNET

> **Controles detallados vigentes.** Este documento conserva ejemplos, variables, matriz de auditoría y checklist
> pre-deploy. Los documentos cortos de esta carpeta organizan los controles por responsabilidad.

Al gestionar datos de **menores de edad** (escolares) y registros psicológicos confidenciales, la seguridad es
prioritaria en todas las capas.

---

## Autenticación — Laravel Sanctum

- Los usuarios humanos usan autenticación SPA stateful de Sanctum mediante sesión y cookie `httpOnly`, `Secure` y
  `SameSite`; React usa `withCredentials` y protección CSRF.
- Los Bearer tokens de `personal_access_tokens` se reservan para el servicio facial y dispositivos registrados.
- El middleware de sesión aplica inactividad máxima de 8 horas para padres/alumnos y 4 horas para personal.
- Logout invalida la sesión en servidor. La expiración o revocación produce 401 y redirección al login.
- Nunca se guardan contraseñas ni tokens en `localStorage`.

---

## Protección contra Fuerza Bruta

```php
// routes/api.php
Route::middleware('throttle:5,1')->post('/auth/login', LoginController::class);
// Máximo 5 intentos por minuto por IP
```

- Mensaje de error genérico: "Credenciales incorrectas" (nunca revelar si el email existe)
- Bloqueo temporal de 15 minutos tras 5 intentos fallidos
- Registro de intentos fallidos en `audit_logs` con IP y timestamp

---

## Autorización — Spatie Laravel Permission + Policies

Doble verificación por endpoint:

1. **Middleware de rol** — verifica que el usuario tiene el rol correcto
2. **Policy de Laravel** — verifica acceso al recurso específico

```php
// app/Modules/Notas/Presentation/Policies/NotaPolicy.php
public function view(User $user, Nota $nota): bool
{
    if ($user->hasRole('padre')) {
        return $user->padreProfile
            ->alumnos()
            ->whereHas('matriculas', fn ($query) => $query->whereKey($nota->matricula_id))
            ->exists();
    }
    if ($user->hasRole('alumno')) {
        return $user->alumnoProfile
            ->matriculas()
            ->whereKey($nota->matricula_id)
            ->exists();
    }
    return $user->hasAnyRole(['coordinador_academico', 'superadmin']);
}
```

**Regla crítica:** nunca se exponen IDs secuenciales en URLs. Todos los recursos usan UUIDs.

---

## Gestión Segura de Cuentas y Roles

- El Promotor conserva `superadmin` y puede delegar la operación diaria a una cuenta específica con rol
  `gestor_usuarios`.
- `administrativo` no incluye permisos de gestión de usuarios; otorgar ese rol a Yanina no permite crear cuentas ni
  asignar roles.
- Solo `superadmin` puede asignar o retirar `gestor_usuarios` y crear o asignar otro `superadmin`.
- `gestor_usuarios` puede administrar cuentas y asignar roles operativos, pero no modificar sus propios roles ni
  permisos.
- Los roles operativos asignables son `toe`, `psicologia`, `auxiliar`, `coordinador_academico`, `administrativo`,
  `docente`, `padre` y `alumno`.
- No existe autorregistro. La vinculación padre-alumno la realiza `gestor_usuarios` o `superadmin`.
- El correo de cada cuenta humana es obligatorio y único. Padres distintos no comparten correo.
- Una persona con varios roles usa una sola cuenta y selecciona contexto de portal; no se duplican cuentas por rol.
- Los permisos sensibles se asignan directamente a cuentas específicas y no se heredan por pertenecer al rol
  `administrativo`.
- Desactivar una cuenta preserva su historial; las cuentas relacionadas con registros no se eliminan físicamente.
- Cada asignación/retiro de rol, restablecimiento de contraseña y activación/desactivación se audita.

---

## Archivos y Almacenamiento

- Los archivos guardados en el VPS **NO son públicos directamente**
- Se accede a ellos a través de un endpoint de Laravel que valida el token antes de servir
- Nginx NO sirve la carpeta `/storage/app/private/` directamente al exterior y no se ejecuta `storage:link`
- Las fotos biométricas se guardan en un bucket Cloudflare R2 separado, privado y sin dominio público
- PostgreSQL guarda solamente la clave `r2_object_key`; Laravel genera accesos temporales de corta duración cuando un
  usuario autorizado necesita revisar una evidencia
- Las credenciales R2 pertenecen al backend y nunca se exponen al navegador ni al servicio facial

```php
// Endpoint protegido para servir archivos
Route::middleware('auth:sanctum')->get('/archivos/{ruta}', function (string $ruta) {
    $path = storage_path('app/private/' . $ruta);
    abort_if(!file_exists($path), 404);
    // Validar que el usuario tiene permiso de ver este archivo
    return response()->file($path, ['Cache-Control' => 'private, max-age=3600']);
});
```

---

## Transporte y Red

- **HTTPS obligatorio** en producción (Let's Encrypt, renovación cada 90 días)
- **HSTS:** `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- **CORS** solo acepta el dominio del frontend del colegio
- **PostgreSQL** no expuesto a internet: solo escucha en `127.0.0.1:5432`
- **Firewall VPS:** solo puertos 80, 443 y SSH abiertos

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

---

## Validación

- Toda entrada de usuario pasa por **Form Requests** de Laravel antes del Use Case
- `declare(strict_types=1)` en todos los archivos PHP
- Consultas vía Eloquent (queries parametrizados, sin SQL raw salvo necesidad)
- Archivos subidos: validación de tipo MIME real (no solo extensión), tamaño máximo configurable
- Las imágenes son procesadas con Intervention Image antes de guardarse (nunca se guarda el original directo)

---

## Auditoría de Acciones Críticas

Se registra en `audit_logs` (PostgreSQL):

| Evento                                    | Qué se registra                        |
|-------------------------------------------|----------------------------------------|
| Login exitoso / fallido                   | user_id, IP, timestamp                 |
| Cambio de nota                            | ID, valor anterior, valor nuevo, quién |
| Creación / modificación de usuario        | Campos cambiados                       |
| Asignación / retiro de rol                | usuario, rol, responsable              |
| Activación / desactivación de cuenta      | usuario, responsable, motivo           |
| Cambio de contraseña                      | user_id, IP, timestamp                 |
| Acceso a reportes globales                | user_id, tipo de reporte               |
| Cambio de concepto o beneficio financiero | valores anteriores/nuevos, Yanina      |
| Movimiento o ajuste de pago               | obligacion_pago_id, tipo, monto, motivo, Yanina    |
| Cambio de tarifa docente                  | docente_id, tarifa anterior/nueva, Yanina |
| Ajuste de descuento docente               | liquidacion_id, monto, motivo, Yanina   |
| Vinculación / desvinculación padre-alumno | IDs involucrados                       |
| Enrolamiento / baja biométrica            | user_id, responsable, modelo_version   |
| Revisión de evento facial                 | evento_id, decisión, revisor           |
| Movimiento manual / salida de emergencia  | persona, tipo, motivo, responsable     |
| Corrección de falta por sincronización    | alumno, evento, estado anterior/nuevo  |
| Alta / revocación de dispositivo          | dispositivo, acción, responsable       |
| Activación / rotación de dispositivo      | dispositivo, responsable, fecha, resultado |
| Acceso a evidencia biométrica             | archivo_id, usuario, motivo            |
| Cierre de liquidación docente             | periodo, totales, Yanina               |
| Cancelación de sesión de clase            | sesión, motivo, Coordinador Académico  |
| Resolución de anomalía de asistencia      | persona, resolución, responsable       |
| Publicación o corrección de notas         | carga, matrícula, valores, responsable |
| Ajuste de deuda pendiente                 | deuda, valores anteriores/nuevos, motivo, responsable |

---

## Variables de Entorno

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cienciasnet
DB_USERNAME=cienciasnet_user
DB_PASSWORD=[contraseña única]

SANCTUM_STATEFUL_DOMAINS=cienciascolegio.pe
SESSION_DOMAIN=.cienciascolegio.pe

FACIAL_SERVICE_TOKEN=[token_largo_y_unico]
FACIAL_SERVICE_URL=http://facial-api:8000
BIOMETRIC_ENCRYPTION_KEY=[clave_independiente_de_APP_KEY]
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_BUCKET_BIOMETRICS=cienciasnet-biometria
R2_ACCESS_KEY_ID=[credencial_privada]
R2_SECRET_ACCESS_KEY=[secreto_privado]
```

**Nunca** commitear `.env` al repositorio. Solo `.env.example` con variables sin valores.

---

## Datos de Menores de Edad — Política de Acceso

- El acceso a datos de un alumno está restringido por Policy a: sus padres vinculados, TOE, Auxiliar, Coordinador
  Académico, Psicología y `superadmin` (Promotor), según el módulo y finalidad
- La vinculación padre-alumno es gestionada exclusivamente por `superadmin` o `gestor_usuarios`, no por autorregistro
- Padres y alumnos solo tienen permisos de consulta sobre sus propios recursos o hijos vinculados.
- Ningún usuario puede acceder a información de alumnos que no le corresponden

---

## Datos Biométricos y Reconocimiento Facial

Los embeddings faciales, fotos de enrolamiento y evidencias son datos sensibles. Su tratamiento sigue minimización,
finalidad específica, retención limitada y acceso restringido.

### Consentimiento y alternativa

- El enrolamiento de alumnos requiere consentimiento expreso y registrado del padre o apoderado autorizado.
- Los docentes deben aceptar el tratamiento biométrico antes del enrolamiento.
- Revocar el consentimiento desactiva inmediatamente el perfil y programa la eliminación del embedding y archivos R2.
- Siempre existe un método manual de asistencia para personas no enroladas o cuando el sistema no esté disponible.
- El consentimiento indica finalidad, datos almacenados, plazo de conservación y procedimiento de revocación.
- El consentimiento se valida al enrolar o actualizar el perfil. No se consulta en cada ingreso/salida; el servicio
  facial recibe únicamente perfiles activos y deja de reconocerlos al revocarse.

### Procesamiento y retención

- Ninguna estación web transmite video continuo; el navegador envía únicamente capturas puntuales
  mediante HTTPS.
- Las capturas rutinarias se procesan en memoria y se eliminan al terminar el reconocimiento.
- Solo se guarda evidencia cuando existe una excepción que requiere revisión.
- La evidencia excepcional tiene `expira_en` obligatorio y una retención recomendada máxima de 30 días.
- Las fotos de enrolamiento se conservan únicamente mientras exista consentimiento activo y sean necesarias para
  regenerar o verificar el perfil.
- Los embeddings se cifran con una clave independiente de `APP_KEY`; la clave no se almacena en PostgreSQL.

### Criterios de aceptación iniciales

- Enrolamiento supervisado con 3 a 5 fotos válidas: frontal y ángulos leves, sin desenfoque ni oclusión importante.
- Aceptación automática inicial con confianza `>= 0.85` y prueba de vida superada.
- Revisión humana entre `0.65` y `0.8499`; rechazo por debajo de `0.65` o sin prueba de vida.
- Los umbrales son configurables y deben calibrarse durante un piloto con alumnos y docentes reales.
- Objetivo de respuesta de 2 segundos, timeout de 5 segundos y flujo manual disponible.
- Ventana inicial anti-duplicado de 30 segundos configurable. Un único perfil facial activo por persona.

### Acceso

| Recurso                       | Acceso permitido                                      |
|-------------------------------|-------------------------------------------------------|
| Estado de enrolamiento        | Superadmin, Auxiliar y Yanina según responsabilidad   |
| Perfil / embedding cifrado    | Backend; servicio facial recibe perfiles activos en memoria |
| Evidencia de alumno           | Superadmin y Auxiliar con motivo registrado           |
| Evidencia de docente          | Superadmin y Yanina con motivo registrado             |
| Eventos sin imagen            | Superadmin y supervisor correspondiente               |
| Tarifas y descuentos docentes | Superadmin y Yanina                                   |

Los padres pueden solicitar información sobre el estado biométrico de sus hijos, pero nunca reciben embeddings ni
acceso directo al bucket.

### Comunicación entre componentes

- Cada estación web y el servicio facial usan credenciales técnicas propias, rotables y revocables.
- Una estación web representa el navegador de una PC, celular o tablet. Puede tener una o varias cámaras autorizadas.
- La estación usa una sesión técnica sin correo, contraseña ni acceso a paneles. Tiene scopes mínimos y registro de
  último contacto. La sesión personal del responsable nunca se copia al dispositivo.
- El enrolamiento del dispositivo usa un código o QR aleatorio, de un solo uso y con expiración máxima de 10 minutos.
  Solo una cuenta humana con `gestionar_dispositivos` puede generarlo.
- La pantalla de captura no concede acceso por conocer su URL. Laravel exige una credencial técnica activa en cada
  petición y entrega respuestas mínimas, sin exponer listas de personas ni perfiles biométricos.
- La sesión técnica se entrega mediante cookie `httpOnly`, `Secure` y `SameSite=Strict`; JavaScript no puede leer su
  secreto. El navegador se comunica con Laravel, nunca directamente con el servicio facial privado.
- Se recomienda abrir la ruta web de captura en modo kiosco o pantalla completa. Retroceder o cambiar la URL no concede
  acceso a otros módulos porque el navegador solo conserva la sesión técnica limitada.
- `superadmin` puede asignar `gestionar_dispositivos` a una cuenta específica. Solo esas cuentas pueden registrar,
  revocar o rotar credenciales; nunca se eliminan registros históricos.
- La IP puede usarse como lista permitida adicional, pero no reemplaza la autenticación por credencial y dispositivo.
- Laravel autentica `/api/estacion/asistencia/capturas`, aplica rate limiting y exige `Idempotency-Key`.
- El servicio facial no se conecta directamente a PostgreSQL ni decide reglas de asistencia.
- Laravel descifra y sincroniza únicamente perfiles activos hacia el servicio facial mediante la red privada; Python
  los conserva solo en memoria.
- El navegador procesa capturas puntuales y no conserva una cola biométrica si pierde Internet.
- Los tokens se guardan como hash cuando sea posible y nunca aparecen en logs.
- R2 permanece privado; cualquier acceso de revisión usa una URL firmada con expiración corta.

### Respuesta ante incidentes

- Desactivar inmediatamente el dispositivo o servicio comprometido.
- Invalidar su credencial, cerrar su sesión técnica y exigir una nueva activación antes de reutilizarlo.
- Revisar y marcar para validación los eventos enviados por el dispositivo durante el periodo comprometido.
- Rotar tokens, credenciales R2 y claves afectadas.
- Preservar únicamente metadatos de auditoría necesarios para investigar.
- Identificar personas afectadas y documentar alcance, acciones y eliminación.
- Verificar que no existan objetos R2 huérfanos después de una baja o revocación.

---

## Confidencialidad de Registros Psicológicos

La tabla `atenciones_psicologia` contiene datos altamente sensibles:

- Acceso restringido exclusivamente al rol `psicologia` y `superadmin` (Promotor), quien tiene acceso completo
- El campo `notas_privadas` **nunca** es expuesto a: docentes, auxiliares, TOE, administrativos ni padres
- Las Policies de Laravel deben verificar el rol antes de cualquier consulta a esta tabla
- No se registra en `audit_logs` el contenido de `notas_privadas` (solo metadatos: quién accedió y cuándo)

---

## Checklist Pre-Deploy

- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] `APP_KEY` generado y único
- [ ] HTTPS activo y HSTS en Nginx
- [ ] Puerto 5432 de PostgreSQL bloqueado externamente
- [ ] Firewall: solo 80, 443 y SSH
- [ ] CORS configurado solo para el dominio del frontend
- [ ] Rate limiting en `/auth/login`
- [ ] `.env` no commiteado
- [ ] Backups cifrados de PostgreSQL, archivos privados y R2 configurados y probados
- [ ] Carpeta `/storage/app/private/` NO accesible directamente vía Nginx y sin `storage:link`
- [ ] Headers de seguridad en Nginx activos
- [ ] Bucket R2 biométrico privado y sin dominio público
- [ ] Credenciales R2 limitadas al bucket biométrico
- [ ] Embeddings cifrados con clave independiente de `APP_KEY`
- [ ] Consentimiento biométrico y método manual alternativo habilitados
- [ ] Retención y eliminación automática de evidencia configuradas
- [ ] Sesiones técnicas de estaciones web y token del servicio facial rotables y revocables
- [ ] Endpoint interno facial con autenticación, rate limiting e idempotencia
- [ ] Accesos y decisiones sobre evidencia biométrica auditados