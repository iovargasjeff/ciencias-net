<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->foreignUuid('reportado_por')->constrained('users');
            $table->timestampTz('fecha');
            $table->enum('tipo', ['conducta', 'tardanza_constante', 'academico', 'otro']);
            $table->enum('severidad', ['leve', 'moderada', 'grave']);
            $table->text('descripcion');
            $table->enum('asignado_a', ['auxiliar', 'toe', 'psicologia']);
            $table->enum('estado', ['abierto', 'derivado_toe', 'derivado_psicologia', 'notificado_padre', 'resuelto']);
            $table->timestampsTz();
        });

        Schema::create('atenciones_psicologia', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incidencia_id')->nullable()->constrained('incidencias')->onDelete('set null');
            $table->foreignUuid('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->foreignUuid('psicologa_id')->constrained('users');
            $table->timestampTz('fecha_atencion');
            $table->text('notas_privadas');
            $table->timestampsTz();
        });

        Schema::create('historial_incidencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incidencia_id')->constrained('incidencias')->onDelete('cascade');
            $table->string('accion', 100);
            $table->text('detalle');
            $table->string('archivo_ruta', 500)->nullable();
            $table->foreignUuid('registrado_por')->constrained('users');
            $table->timestampTz('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_incidencias');
        Schema::dropIfExists('atenciones_psicologia');
        Schema::dropIfExists('incidencias');
    }
};
