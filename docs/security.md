# Seguridad — CienciasNET

Al gestionar datos de **menores de edad** (escolares) y registros psicológicos confidenciales, la seguridad es
prioritaria en todas las capas.

---

## Autenticación — Laravel Sanctum

- Tokens SPA en la tabla `personal_access_tokens` de PostgreSQL
- Expiración: 8 horas para alumnos/padres, 4 horas para administradores
- Logout invalida el token en servidor (no solo en el cliente)
- Token expirado → respuesta 401 → React redirige al login automáticamente

```php
// config/sanctum.php
'expiration' => 480, // 8 horas en minutos
```

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
            ->where('alumno_id', $nota->alumno_id)
            ->exists();
    }
    return $user->hasAnyRole(['coordinador', 'administrador', 'director']);
}
```

**Regla crítica:** nunca se exponen IDs secuenciales en URLs. Todos los recursos usan UUIDs.

---

## Archivos y Almacenamiento

- Los archivos guardados en el VPS **NO son públicos directamente**
- Se accede a ellos a través de un endpoint de Laravel que valida el token antes de servir
- Nginx NO sirve la carpeta `/storage/app/public/` directamente al exterior

```php
// Endpoint protegido para servir archivos
Route::middleware('auth:sanctum')->get('/archivos/{ruta}', function (string $ruta) {
    $path = storage_path('app/public/' . $ruta);
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
| Cambio de contraseña                      | user_id, IP, timestamp                 |
| Acceso a reportes globales                | user_id, tipo de reporte               |
| Vinculación / desvinculación padre-alumno | IDs involucrados                       |

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
```

**Nunca** commitear `.env` al repositorio. Solo `.env.example` con variables sin valores.

---

## Datos de Menores de Edad — Política de Acceso

- El acceso a datos de un alumno está restringido por Policy a: sus padres vinculados, TOE, Auxiliar, Coordinador
  Académico, Psicología y Dirección
- La vinculación padre-alumno es gestionada exclusivamente por el administrador (superadmin), no por autoregistro
- Ningún usuario puede acceder a información de alumnos que no le corresponden

---

## Confidencialidad de Registros Psicológicos

La tabla `atenciones_psicologia` contiene datos altamente sensibles:

- Acceso restringido exclusivamente al rol `psicologia` y `superadmin` (Dirección)
- El campo `notas_privadas` **nunca** es expuesto a: docentes, auxiliares, TOE, administrativos ni padres
- Las Policies de Laravel deben verificar el rol antes de cualquier consulta a esta tabla
- No se registra en `audit_logs` el contenido de `notas_privadas` (solo metadatos: quién accedió y cuándo)
- Las derivaciones a UGEL desde TOE no incluyen las notas privadas de psicología

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
- [ ] Backups automáticos de PostgreSQL configurados
- [ ] Carpeta `/storage/app/public/` NO accesible directamente vía Nginx
- [ ] Headers de seguridad en Nginx activos
