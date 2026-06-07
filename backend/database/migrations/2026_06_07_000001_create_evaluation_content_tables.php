<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-004: add-evaluation-content-schema
 *
 * Tablas: examenes, notas, reportes_academicos, materiales,
 *         horarios, eventos_calendario, comunicados,
 *         comunicado_lecturas, notificaciones
 *
 * Sin contrato HTTP — solo persistencia para BE-018..BE-023.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------ //
        // EXÁMENES                                                             //
        // ------------------------------------------------------------------ //
        Schema::create('examenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carga_academica_id')
                ->constrained('carga_academica')
                ->restrictOnDelete();
            $table->string('titulo', 200);
            $table->date('fecha_aplicacion');
            $table->string('periodo_nombre', 50);
            $table->string('canal', 20)->default('general'); // general|ciencias|letras
            $table->integer('total_preguntas');
            $table->decimal('puntaje_maximo', 6, 2);
            $table->string('estado', 20)->default('borrador'); // borrador|listo|publicado|cerrado
            $table->foreignUuid('publicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('publicado_en')->nullable();
            $table->timestampsTz();

            $table->index(['carga_academica_id', 'estado']);
            $table->index(['fecha_aplicacion', 'estado']);
            $table->index(['periodo_nombre', 'canal']);
        });

        // ------------------------------------------------------------------ //
        // NOTAS                                                                //
        // ------------------------------------------------------------------ //
        Schema::create('notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('examen_id')
                ->constrained('examenes')
                ->restrictOnDelete();
            $table->foreignUuid('matricula_id')
                ->constrained('matriculas')
                ->restrictOnDelete();
            $table->decimal('puntaje', 6, 2)->nullable(); // Nullable para ausente/exonerado
            $table->string('estado', 20)->default('pendiente'); // registrada|ausente|exonerado|pendiente
            $table->text('observacion')->nullable();
            $table->integer('puesto_ranking')->nullable(); // Calculado al publicar
            $table->foreignUuid('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['examen_id', 'matricula_id']);
            $table->index(['examen_id', 'estado']);
            $table->index(['matricula_id', 'estado']);
        });

        // ------------------------------------------------------------------ //
        // REPORTES ACADÉMICOS                                                  //
        // ------------------------------------------------------------------ //
        Schema::create('reportes_academicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matricula_id')
                ->constrained('matriculas')
                ->restrictOnDelete();
            $table->string('periodo_nombre', 50);
            $table->string('tipo', 30); // libreta|reporte_academia
            $table->string('archivo_ruta', 500);
            $table->timestampTz('publicado_en');
            $table->foreignUuid('generado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->index(['matricula_id', 'periodo_nombre']);
            $table->index(['tipo', 'publicado_en']);
        });

        // ------------------------------------------------------------------ //
        // MATERIALES                                                           //
        // ------------------------------------------------------------------ //
        Schema::create('materiales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 20); // pdf|video|enlace|otro
            $table->string('ruta_o_url', 500);
            $table->foreignUuid('carga_academica_id')
                ->nullable()
                ->constrained('carga_academica')
                ->nullOnDelete(); // Nullable = material general
            $table->foreignUuid('subido_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->smallInteger('semana')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->index(['carga_academica_id', 'activo']);
            $table->index(['tipo', 'activo']);
        });

        // ------------------------------------------------------------------ //
        // HORARIOS                                                             //
        // ------------------------------------------------------------------ //
        Schema::create('horarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('carga_academica_id')
                ->constrained('carga_academica')
                ->restrictOnDelete();
            $table->smallInteger('dia_semana'); // 1=Lun … 7=Dom
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 50)->nullable();
            $table->timestampsTz();

            $table->index(['carga_academica_id', 'dia_semana']);
        });

        // ------------------------------------------------------------------ //
        // EVENTOS DE CALENDARIO                                                //
        // ------------------------------------------------------------------ //
        Schema::create('eventos_calendario', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('periodo_academico_id')
                ->constrained('periodos_academicos')
                ->restrictOnDelete();
            $table->string('tipo', 30); // evento|examen|simulacro|no_laboral
            $table->string('titulo', 200);
            $table->timestampTz('fecha_inicio');
            $table->timestampTz('fecha_fin');
            $table->foreignUuid('seccion_id')
                ->nullable()
                ->constrained('secciones')
                ->nullOnDelete(); // Nullable = evento general
            $table->foreignUuid('creado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->index(['periodo_academico_id', 'tipo']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });

        // ------------------------------------------------------------------ //
        // COMUNICADOS                                                          //
        // ------------------------------------------------------------------ //
        Schema::create('comunicados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->foreignUuid('publicado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->jsonb('destinatarios'); // {"roles": ["padre","docente"], "grados": [1,2,3]}
            $table->boolean('importante')->default(false); // Dispara notificación por correo
            $table->timestampTz('fecha_publicacion');
            $table->timestampsTz();

            $table->index(['fecha_publicacion', 'importante']);
            $table->index('publicado_por');
        });

        // ------------------------------------------------------------------ //
        // LECTURAS DE COMUNICADOS                                              //
        // ------------------------------------------------------------------ //
        Schema::create('comunicado_lecturas', function (Blueprint $table) {
            $table->foreignUuid('comunicado_id')
                ->constrained('comunicados')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestampTz('leido_en');
            $table->timestampTz('archivado_en')->nullable();
            $table->primary(['comunicado_id', 'user_id']); // Uniqueness por PK compuesta

            $table->index('user_id');
        });

        // ------------------------------------------------------------------ //
        // NOTIFICACIONES                                                       //
        // ------------------------------------------------------------------ //
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete(); // Destinatario
            $table->string('tipo', 100); // nota_publicada|asistencia|pago|comunicado
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->jsonb('datos'); // IDs y ruta interna, sin datos privados innecesarios
            $table->string('canal', 20)->default('panel'); // panel|correo
            $table->string('estado', 20)->default('pendiente'); // pendiente|enviada|fallida|leida
            $table->timestampTz('enviada_en')->nullable();
            $table->timestampTz('leida_en')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['user_id', 'estado']);
            $table->index(['user_id', 'canal', 'estado']);
            $table->index(['tipo', 'created_at']);
        });

        // ------------------------------------------------------------------ //
        // CONSTRAINTS PostgreSQL (CHECK + índices parciales)                   //
        // ------------------------------------------------------------------ //
        if (DB::getDriverName() === 'pgsql') {
            // examenes: puntaje_maximo > 0 y total_preguntas > 0
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT examenes_puntaje_positivo CHECK (puntaje_maximo > 0)');
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT examenes_preguntas_positivas CHECK (total_preguntas > 0)');
            DB::statement("ALTER TABLE examenes ADD CONSTRAINT examenes_canal_valido CHECK (canal IN ('general','ciencias','letras'))");
            DB::statement("ALTER TABLE examenes ADD CONSTRAINT examenes_estado_valido CHECK (estado IN ('borrador','listo','publicado','cerrado'))");

            // notas: puntaje entre 0 y puntaje_maximo lo valida la app; aquí solo >= 0
            DB::statement('ALTER TABLE notas ADD CONSTRAINT notas_puntaje_no_negativo CHECK (puntaje IS NULL OR puntaje >= 0)');
            DB::statement("ALTER TABLE notas ADD CONSTRAINT notas_estado_valido CHECK (estado IN ('registrada','ausente','exonerado','pendiente'))");

            // horarios: hora_fin > hora_inicio
            DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_horas_validas CHECK (hora_fin > hora_inicio)');
            DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_dia_valido CHECK (dia_semana BETWEEN 1 AND 7)');

            // eventos_calendario: fecha_fin >= fecha_inicio
            DB::statement('ALTER TABLE eventos_calendario ADD CONSTRAINT eventos_fechas_validas CHECK (fecha_fin >= fecha_inicio)');
            DB::statement("ALTER TABLE eventos_calendario ADD CONSTRAINT eventos_tipo_valido CHECK (tipo IN ('evento','examen','simulacro','no_laboral'))");

            // notificaciones: canal y estado
            DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_canal_valido CHECK (canal IN ('panel','correo'))");
            DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_estado_valido CHECK (estado IN ('pendiente','enviada','fallida','leida'))");

            // índices parciales para consultas frecuentes
            DB::statement('CREATE INDEX notas_ranking_parcial ON notas (examen_id, puesto_ranking) WHERE puesto_ranking IS NOT NULL');
            DB::statement("CREATE INDEX notificaciones_pendientes_idx ON notificaciones (user_id, created_at) WHERE estado = 'pendiente'");
            DB::statement("CREATE INDEX examenes_publicados_idx ON examenes (carga_academica_id, fecha_aplicacion) WHERE estado IN ('publicado','cerrado')");
        }
    }

    public function down(): void
    {
        // Eliminar constraints primero para evitar errores por dependencias
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS examenes_publicados_idx');
            DB::statement('DROP INDEX IF EXISTS notificaciones_pendientes_idx');
            DB::statement('DROP INDEX IF EXISTS notas_ranking_parcial');
        }

        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('comunicado_lecturas');
        Schema::dropIfExists('comunicados');
        Schema::dropIfExists('eventos_calendario');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('materiales');
        Schema::dropIfExists('reportes_academicos');
        Schema::dropIfExists('notas');
        Schema::dropIfExists('examenes');
    }
};
