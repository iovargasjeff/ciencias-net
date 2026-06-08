<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_tecnicas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 150);
            $table->string('tipo', 30);
            $table->string('token_hash', 255);
            $table->json('scopes');
            $table->boolean('activo')->default(true);
            $table->foreignUuid('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('ultimo_contacto')->nullable();
            $table->timestampTz('token_rotado_en')->nullable();
            $table->timestampsTz();
            $table->index(['tipo', 'activo']);
        });

        Schema::create('consentimientos_biometricos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('estado', 20);
            $table->foreignUuid('otorgado_por')->constrained('users')->restrictOnDelete();
            $table->string('documento_version', 30);
            $table->timestampTz('otorgado_en')->nullable();
            $table->timestampTz('revocado_en')->nullable();
            $table->text('motivo_revocacion')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'estado']);
        });

        Schema::create('perfiles_faciales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->binary('embedding_cifrado');
            $table->string('modelo_version', 100);
            $table->decimal('calidad', 5, 4);
            $table->boolean('activo')->default(true);
            $table->foreignUuid('enrolado_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('enrolado_en');
            $table->timestampTz('ultima_actualizacion_en');
            $table->timestampsTz();
            $table->index(['user_id', 'activo']);
        });

        Schema::create('archivos_biometricos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('perfil_facial_id')->nullable()->constrained('perfiles_faciales')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('r2_object_key', 500);
            $table->string('sha256', 64);
            $table->string('mime_type', 100);
            $table->timestampTz('expira_en')->nullable();
            $table->timestampTz('eliminado_en')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique('r2_object_key');
            $table->index(['user_id', 'tipo']);
            $table->index('expira_en');
        });

        Schema::create('estaciones_biometricas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 150);
            $table->string('ubicacion', 200);
            $table->string('tipo_equipo', 30);
            $table->foreignUuid('cuenta_tecnica_id')->unique()->constrained('cuentas_tecnicas')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestampTz('ultimo_contacto')->nullable();
            $table->json('configuracion');
            $table->timestampTz('activado_en')->nullable();
            $table->timestampTz('revocado_en')->nullable();
            $table->timestampsTz();
            $table->index(['activo', 'tipo_equipo']);
        });

        Schema::create('camaras_estacion', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('estacion_id')->constrained('estaciones_biometricas')->cascadeOnDelete();
            $table->string('device_id_navegador', 255);
            $table->string('nombre', 150);
            $table->string('ubicacion', 200)->nullable();
            $table->string('modo', 30);
            $table->boolean('activo')->default(true);
            $table->unique(['estacion_id', 'device_id_navegador']);
            $table->index(['estacion_id', 'activo']);
        });

        Schema::create('activaciones_estacion', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('estacion_id')->constrained('estaciones_biometricas')->cascadeOnDelete();
            $table->string('codigo_hash', 255);
            $table->timestampTz('expira_en');
            $table->timestampTz('usado_en')->nullable();
            $table->foreignUuid('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['estacion_id', 'expira_en']);
            $table->index(['usado_en', 'expira_en']);
        });

        Schema::create('eventos_reconocimiento', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 191)->unique();
            $table->foreignUuid('estacion_id')->constrained('estaciones_biometricas')->restrictOnDelete();
            $table->foreignUuid('camara_estacion_id')->constrained('camaras_estacion')->restrictOnDelete();
            $table->foreignUuid('cuenta_tecnica_id')->constrained('cuentas_tecnicas')->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_persona', 30);
            $table->string('tipo_evento_resuelto', 30)->nullable();
            $table->decimal('confianza', 5, 4);
            $table->boolean('prueba_vida_superada');
            $table->string('estado', 30);
            $table->string('motivo_estado', 255)->nullable();
            $table->foreignUuid('evidencia_archivo_id')->nullable()->constrained('archivos_biometricos')->nullOnDelete();
            $table->timestampTz('capturado_en');
            $table->timestampTz('recibido_en');
            $table->foreignUuid('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revisado_en')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'capturado_en']);
            $table->index(['estacion_id', 'capturado_en']);
        });

        Schema::create('asistencias_alumnos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumno_id')->constrained('alumnos')->restrictOnDelete();
            $table->date('fecha');
            $table->time('primer_ingreso')->nullable();
            $table->time('ultima_salida')->nullable();
            $table->string('estado', 30);
            $table->boolean('presencia_abierta')->default(false);
            $table->foreignUuid('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['alumno_id', 'fecha']);
            $table->index(['fecha', 'estado']);
        });

        Schema::create('asistencias_docentes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->date('fecha');
            $table->time('primer_ingreso')->nullable();
            $table->time('ultima_salida')->nullable();
            $table->string('estado', 30);
            $table->integer('minutos_tardanza')->default(0);
            $table->foreignUuid('docente_sustituto_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->foreignUuid('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['docente_id', 'fecha']);
            $table->index(['fecha', 'estado']);
        });

        Schema::create('movimientos_asistencia', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('asistencia_alumno_id')->nullable()->constrained('asistencias_alumnos')->cascadeOnDelete();
            $table->foreignUuid('asistencia_docente_id')->nullable()->constrained('asistencias_docentes')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('motivo', 30)->default('regular');
            $table->text('observacion')->nullable();
            $table->timestampTz('ocurrido_en');
            $table->string('origen', 30);
            $table->foreignUuid('estacion_id')->nullable()->constrained('estaciones_biometricas')->nullOnDelete();
            $table->foreignUuid('camara_estacion_id')->nullable()->constrained('camaras_estacion')->nullOnDelete();
            $table->foreignUuid('evento_reconocimiento_id')->nullable()->constrained('eventos_reconocimiento')->nullOnDelete();
            $table->decimal('confianza_reconocimiento', 5, 4)->nullable();
            $table->boolean('notificacion_enviada')->default(false);
            $table->foreignUuid('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('cuenta_tecnica_id')->nullable()->constrained('cuentas_tecnicas')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['asistencia_alumno_id', 'ocurrido_en']);
            $table->index(['asistencia_docente_id', 'ocurrido_en']);
            $table->index(['evento_reconocimiento_id']);
        });

        Schema::create('anomalias_asistencia', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('asistencia_alumno_id')->nullable()->constrained('asistencias_alumnos')->cascadeOnDelete();
            $table->foreignUuid('asistencia_docente_id')->nullable()->constrained('asistencias_docentes')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('estado', 30)->default('pendiente');
            $table->text('detalle');
            $table->foreignUuid('asignado_a')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolucion')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('resuelto_en')->nullable();
            $table->index(['estado', 'created_at']);
            $table->index(['asignado_a', 'estado']);
        });

        Schema::create('configuraciones_jornada', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 150);
            $table->foreignUuid('grado_id')->nullable()->constrained('grados')->cascadeOnDelete();
            $table->unsignedSmallInteger('dia_semana');
            $table->time('hora_limite_puntual');
            $table->time('hora_cierre_asistencia');
            $table->boolean('activo')->default(true);
            $table->foreignUuid('configurado_por')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['grado_id', 'dia_semana', 'activo']);
        });

        Schema::create('sesiones_clase', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('carga_academica_id')->constrained('carga_academica')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado', 30)->default('programada');
            $table->text('motivo_cancelacion')->nullable();
            $table->foreignUuid('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('docente_sustituto_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->foreignUuid('revisado_planilla_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['carga_academica_id', 'fecha', 'hora_inicio']);
            $table->index(['fecha', 'estado']);
        });

        Schema::create('tarifas_docentes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->decimal('tarifa_hora', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->foreignUuid('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['docente_id', 'vigente_desde']);
        });

        Schema::create('liquidaciones_descuento_docentes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->unsignedSmallInteger('periodo_anio');
            $table->unsignedSmallInteger('periodo_mes');
            $table->decimal('tarifa_hora_snapshot', 10, 2);
            $table->integer('minutos_tardanza')->default(0);
            $table->decimal('horas_falta_justificada', 8, 2)->default(0);
            $table->decimal('horas_falta_injustificada', 8, 2)->default(0);
            $table->decimal('monto_tardanza', 10, 2)->default(0);
            $table->decimal('monto_falta_justificada', 10, 2)->default(0);
            $table->decimal('monto_falta_injustificada', 10, 2)->default(0);
            $table->decimal('monto_ajuste', 10, 2)->default(0);
            $table->text('motivo_ajuste')->nullable();
            $table->decimal('monto_total_descuento', 10, 2);
            $table->string('estado', 20)->default('borrador');
            $table->foreignUuid('calculado_por')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cerrada_en')->nullable();
            $table->timestampsTz();
            $table->unique(['docente_id', 'periodo_anio', 'periodo_mes']);
            $table->index(['periodo_anio', 'periodo_mes', 'estado']);
        });

        $this->addPostgresConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_descuento_docentes');
        Schema::dropIfExists('tarifas_docentes');
        Schema::dropIfExists('sesiones_clase');
        Schema::dropIfExists('configuraciones_jornada');
        Schema::dropIfExists('anomalias_asistencia');
        Schema::dropIfExists('movimientos_asistencia');
        Schema::dropIfExists('asistencias_docentes');
        Schema::dropIfExists('asistencias_alumnos');
        Schema::dropIfExists('eventos_reconocimiento');
        Schema::dropIfExists('activaciones_estacion');
        Schema::dropIfExists('camaras_estacion');
        Schema::dropIfExists('estaciones_biometricas');
        Schema::dropIfExists('archivos_biometricos');
        Schema::dropIfExists('perfiles_faciales');
        Schema::dropIfExists('consentimientos_biometricos');
        Schema::dropIfExists('cuentas_tecnicas');
    }

    private function addPostgresConstraints(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE cuentas_tecnicas ADD CONSTRAINT cuentas_tecnicas_tipo_check CHECK (tipo IN ('estacion_web', 'servicio_facial'))");
        DB::statement("ALTER TABLE consentimientos_biometricos ADD CONSTRAINT consentimientos_estado_check CHECK (estado IN ('otorgado', 'revocado', 'pendiente'))");
        DB::statement("CREATE UNIQUE INDEX consentimientos_biometricos_un_otorgado_por_user ON consentimientos_biometricos (user_id) WHERE estado = 'otorgado'");
        DB::statement('ALTER TABLE perfiles_faciales ADD CONSTRAINT perfiles_faciales_calidad_check CHECK (calidad >= 0 AND calidad <= 1)');
        DB::statement('CREATE UNIQUE INDEX perfiles_faciales_un_activo_por_user ON perfiles_faciales (user_id) WHERE activo = true');
        DB::statement("ALTER TABLE archivos_biometricos ADD CONSTRAINT archivos_biometricos_tipo_check CHECK (tipo IN ('enrolamiento', 'evidencia_excepcion'))");
        DB::statement("ALTER TABLE archivos_biometricos ADD CONSTRAINT archivos_biometricos_expira_evidencia_check CHECK (tipo <> 'evidencia_excepcion' OR expira_en IS NOT NULL)");
        DB::statement("ALTER TABLE estaciones_biometricas ADD CONSTRAINT estaciones_tipo_equipo_check CHECK (tipo_equipo IN ('pc', 'celular', 'tablet', 'otro'))");
        DB::statement("ALTER TABLE camaras_estacion ADD CONSTRAINT camaras_modo_check CHECK (modo IN ('entrada', 'salida', 'bidireccional'))");
        DB::statement('ALTER TABLE activaciones_estacion ADD CONSTRAINT activaciones_expiran_en_diez_minutos CHECK (expira_en <= created_at + interval \'10 minutes\')');
        DB::statement("ALTER TABLE eventos_reconocimiento ADD CONSTRAINT eventos_tipo_persona_check CHECK (tipo_persona IN ('alumno', 'docente', 'desconocido'))");
        DB::statement("ALTER TABLE eventos_reconocimiento ADD CONSTRAINT eventos_tipo_resuelto_check CHECK (tipo_evento_resuelto IS NULL OR tipo_evento_resuelto IN ('ingreso', 'salida', 'reingreso'))");
        DB::statement("ALTER TABLE eventos_reconocimiento ADD CONSTRAINT eventos_estado_check CHECK (estado IN ('aceptado', 'pendiente_revision', 'rechazado', 'duplicado'))");
        DB::statement('ALTER TABLE eventos_reconocimiento ADD CONSTRAINT eventos_confianza_check CHECK (confianza >= 0 AND confianza <= 1)');
        DB::statement("ALTER TABLE eventos_reconocimiento ADD CONSTRAINT eventos_evidencia_solo_excepcion_check CHECK (evidencia_archivo_id IS NULL OR estado <> 'aceptado')");
        DB::statement("CREATE INDEX eventos_reconocimiento_pendientes_idx ON eventos_reconocimiento (estado, capturado_en) WHERE estado = 'pendiente_revision'");
        DB::statement("ALTER TABLE asistencias_alumnos ADD CONSTRAINT asistencias_alumnos_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta_injustificada', 'falta_justificada'))");
        DB::statement("ALTER TABLE asistencias_docentes ADD CONSTRAINT asistencias_docentes_estado_check CHECK (estado IN ('presente', 'falta_justificada', 'falta_injustificada'))");
        DB::statement('ALTER TABLE asistencias_docentes ADD CONSTRAINT asistencias_docentes_minutos_check CHECK (minutos_tardanza >= 0)');
        DB::statement("ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_tipo_check CHECK (tipo IN ('ingreso', 'salida', 'reingreso'))");
        DB::statement("ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_motivo_check CHECK (motivo IN ('regular', 'temporal', 'emergencia', 'otro'))");
        DB::statement("ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_origen_check CHECK (origen IN ('facial', 'manual'))");
        DB::statement('ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_una_asistencia_check CHECK ((asistencia_alumno_id IS NOT NULL)::int + (asistencia_docente_id IS NOT NULL)::int = 1)');
        DB::statement('ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_un_actor_check CHECK ((registrado_por IS NOT NULL)::int + (cuenta_tecnica_id IS NOT NULL)::int = 1)');
        DB::statement("ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_observacion_requerida_check CHECK (motivo NOT IN ('emergencia', 'otro') OR observacion IS NOT NULL)");
        DB::statement('ALTER TABLE movimientos_asistencia ADD CONSTRAINT movimientos_confianza_check CHECK (confianza_reconocimiento IS NULL OR (confianza_reconocimiento >= 0 AND confianza_reconocimiento <= 1))');
        DB::statement("ALTER TABLE anomalias_asistencia ADD CONSTRAINT anomalias_tipo_check CHECK (tipo IN ('sin_salida', 'sin_ingreso', 'secuencia_invalida', 'otro'))");
        DB::statement("ALTER TABLE anomalias_asistencia ADD CONSTRAINT anomalias_estado_check CHECK (estado IN ('pendiente', 'resuelta', 'descartada'))");
        DB::statement('ALTER TABLE anomalias_asistencia ADD CONSTRAINT anomalias_una_asistencia_check CHECK ((asistencia_alumno_id IS NOT NULL)::int + (asistencia_docente_id IS NOT NULL)::int = 1)');
        DB::statement('ALTER TABLE configuraciones_jornada ADD CONSTRAINT configuraciones_dia_check CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE configuraciones_jornada ADD CONSTRAINT configuraciones_horas_check CHECK (hora_cierre_asistencia > hora_limite_puntual)');
        DB::statement("ALTER TABLE sesiones_clase ADD CONSTRAINT sesiones_estado_check CHECK (estado IN ('programada', 'realizada', 'cancelada', 'docente_ausente'))");
        DB::statement('ALTER TABLE sesiones_clase ADD CONSTRAINT sesiones_horas_check CHECK (hora_fin > hora_inicio)');
        DB::statement('ALTER TABLE tarifas_docentes ADD CONSTRAINT tarifas_monto_check CHECK (tarifa_hora >= 0)');
        DB::statement('ALTER TABLE tarifas_docentes ADD CONSTRAINT tarifas_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
        DB::statement('ALTER TABLE liquidaciones_descuento_docentes ADD CONSTRAINT liquidaciones_periodo_check CHECK (periodo_mes BETWEEN 1 AND 12)');
        DB::statement("ALTER TABLE liquidaciones_descuento_docentes ADD CONSTRAINT liquidaciones_estado_check CHECK (estado IN ('borrador', 'cerrada'))");
        DB::statement('ALTER TABLE liquidaciones_descuento_docentes ADD CONSTRAINT liquidaciones_montos_check CHECK (minutos_tardanza >= 0 AND horas_falta_justificada >= 0 AND horas_falta_injustificada >= 0 AND monto_tardanza >= 0 AND monto_falta_justificada >= 0 AND monto_falta_injustificada >= 0 AND monto_total_descuento >= 0)');
    }
};
