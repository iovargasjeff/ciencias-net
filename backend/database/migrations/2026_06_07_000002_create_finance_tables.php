<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-003: add-finance-schema
 *
 * Sin contrato HTTP — persistencia financiera para BE-014..BE-017.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_financieras', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('periodo_academico_id')
                ->constrained('periodos_academicos')
                ->restrictOnDelete();
            $table->smallInteger('dia_generacion_mensualidad');
            $table->smallInteger('dia_vencimiento_mensualidad');
            $table->foreignUuid('configurado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestampsTz();

            $table->index(['periodo_academico_id', 'vigente_desde']);
            $table->index(['configurado_por', 'created_at']);
        });

        Schema::create('conceptos_pago', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 200);
            $table->string('tipo', 30);
            $table->foreignUuid('periodo_academico_id')
                ->constrained('periodos_academicos')
                ->restrictOnDelete();
            $table->smallInteger('periodo_anio');
            $table->smallInteger('periodo_mes')->nullable();
            $table->decimal('monto_base', 8, 2);
            $table->decimal('descuento_pronto_pago', 8, 2)->default(0);
            $table->date('fecha_limite_pronto_pago')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->timestampTz('bloqueado_en')->nullable();
            $table->foreignUuid('creado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['periodo_academico_id', 'tipo', 'estado']);
            $table->index(['periodo_anio', 'periodo_mes']);
            $table->index(['creado_por', 'created_at']);
        });

        Schema::create('beneficios_alumnos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumno_id')
                ->constrained('alumnos')
                ->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('modalidad', 20);
            $table->decimal('valor', 8, 2)->nullable();
            $table->boolean('aplica_mensualidad')->default(true);
            $table->boolean('aplica_matricula')->default(false);
            $table->boolean('aplica_cuota_ingreso')->default(false);
            $table->boolean('acumulable_pronto_pago')->default(false);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->text('motivo');
            $table->boolean('activo')->default(true);
            $table->foreignUuid('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['alumno_id', 'activo', 'vigente_desde']);
            $table->index(['registrado_por', 'created_at']);
        });

        Schema::create('obligaciones_pago', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumno_id')
                ->constrained('alumnos')
                ->restrictOnDelete();
            $table->foreignUuid('concepto_id')
                ->constrained('conceptos_pago')
                ->restrictOnDelete();
            $table->decimal('monto_base_snapshot', 8, 2);
            $table->foreignUuid('beneficio_id')
                ->nullable()
                ->constrained('beneficios_alumnos')
                ->restrictOnDelete();
            $table->decimal('monto_beneficio_snapshot', 8, 2)->default(0);
            $table->decimal('descuento_pronto_pago_aplicado', 8, 2)->default(0);
            $table->decimal('monto_ordinario_snapshot', 8, 2);
            $table->decimal('monto_pronto_pago_snapshot', 8, 2);
            $table->date('fecha_limite_pronto_pago_snapshot')->nullable();
            $table->decimal('monto_cobrado', 8, 2)->nullable();
            $table->date('fecha_vencimiento');
            $table->timestampTz('fecha_pago')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignUuid('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignUuid('actualizado_finanzas_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('motivo_ultima_modificacion')->nullable();
            $table->timestampsTz();

            $table->index(['alumno_id', 'estado', 'fecha_vencimiento']);
            $table->index(['concepto_id', 'estado']);
            $table->index(['beneficio_id']);
            $table->index(['registrado_por', 'created_at']);
            $table->index(['actualizado_finanzas_por', 'updated_at']);
        });

        Schema::create('movimientos_pago', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('obligacion_pago_id')
                ->constrained('obligaciones_pago')
                ->restrictOnDelete();
            $table->string('tipo', 20);
            $table->decimal('monto', 8, 2);
            $table->string('medio_pago', 30)->nullable();
            $table->string('referencia', 150)->nullable();
            $table->string('numero_recibo', 50)->unique();
            $table->string('comprobante_ruta', 500)->nullable();
            $table->text('motivo')->nullable();
            $table->foreignUuid('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['obligacion_pago_id', 'tipo']);
            $table->index(['registrado_por', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE configuraciones_financieras ADD CONSTRAINT config_financiera_dia_generacion_valido CHECK (dia_generacion_mensualidad BETWEEN 1 AND 31)');
            DB::statement('ALTER TABLE configuraciones_financieras ADD CONSTRAINT config_financiera_dia_vencimiento_valido CHECK (dia_vencimiento_mensualidad BETWEEN 1 AND 31)');
            DB::statement('ALTER TABLE configuraciones_financieras ADD CONSTRAINT config_financiera_vigencia_valida CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');

            DB::statement("ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_tipo_valido CHECK (tipo IN ('cuota_ingreso','matricula','mensualidad','otro'))");
            DB::statement("ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_estado_valido CHECK (estado IN ('borrador','vigente','cerrado'))");
            DB::statement('ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_periodo_mes_valido CHECK (periodo_mes IS NULL OR periodo_mes BETWEEN 1 AND 12)');
            DB::statement("ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_mensualidad_mes_requerido CHECK ((tipo = 'mensualidad' AND periodo_mes IS NOT NULL) OR (tipo <> 'mensualidad'))");
            DB::statement('ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_monto_base_positivo CHECK (monto_base > 0)');
            DB::statement('ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_descuento_no_negativo CHECK (descuento_pronto_pago >= 0)');
            DB::statement('ALTER TABLE conceptos_pago ADD CONSTRAINT conceptos_pago_descuento_no_supera_base CHECK (descuento_pronto_pago <= monto_base)');

            DB::statement("ALTER TABLE beneficios_alumnos ADD CONSTRAINT beneficios_alumnos_tipo_valido CHECK (tipo IN ('beca','descuento'))");
            DB::statement("ALTER TABLE beneficios_alumnos ADD CONSTRAINT beneficios_alumnos_modalidad_valida CHECK (modalidad IN ('porcentaje','monto_fijo','exoneracion'))");
            DB::statement('ALTER TABLE beneficios_alumnos ADD CONSTRAINT beneficios_alumnos_vigencia_valida CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
            DB::statement("ALTER TABLE beneficios_alumnos ADD CONSTRAINT beneficios_alumnos_valor_por_modalidad CHECK ((modalidad = 'porcentaje' AND valor > 0 AND valor <= 100) OR (modalidad = 'monto_fijo' AND valor > 0) OR (modalidad = 'exoneracion' AND valor IS NULL))");
            DB::statement('ALTER TABLE beneficios_alumnos ADD CONSTRAINT beneficios_alumnos_alcance_requerido CHECK (aplica_mensualidad OR aplica_matricula OR aplica_cuota_ingreso)');

            DB::statement("ALTER TABLE obligaciones_pago ADD CONSTRAINT obligaciones_pago_estado_valido CHECK (estado IN ('pendiente','pagado','vencido'))");
            DB::statement('ALTER TABLE obligaciones_pago ADD CONSTRAINT obligaciones_pago_montos_no_negativos CHECK (monto_base_snapshot > 0 AND monto_beneficio_snapshot >= 0 AND descuento_pronto_pago_aplicado >= 0 AND monto_ordinario_snapshot >= 0 AND monto_pronto_pago_snapshot >= 0 AND (monto_cobrado IS NULL OR monto_cobrado > 0))');
            DB::statement('ALTER TABLE obligaciones_pago ADD CONSTRAINT obligaciones_pago_montos_consistentes CHECK (monto_beneficio_snapshot <= monto_base_snapshot AND monto_pronto_pago_snapshot <= monto_ordinario_snapshot)');
            DB::statement("ALTER TABLE obligaciones_pago ADD CONSTRAINT obligaciones_pago_pago_estado_consistente CHECK ((estado = 'pagado' AND fecha_pago IS NOT NULL AND monto_cobrado IS NOT NULL) OR (estado <> 'pagado'))");
            DB::statement('ALTER TABLE obligaciones_pago ADD CONSTRAINT obligaciones_pago_motivo_si_actualiza CHECK (actualizado_finanzas_por IS NULL OR motivo_ultima_modificacion IS NOT NULL)');
            DB::statement("CREATE INDEX obligaciones_pago_pendientes_idx ON obligaciones_pago (alumno_id, fecha_vencimiento) WHERE estado IN ('pendiente','vencido')");

            DB::statement("ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_tipo_valido CHECK (tipo IN ('pago','anulacion','devolucion'))");
            DB::statement("ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_medio_valido CHECK (medio_pago IS NULL OR medio_pago IN ('efectivo','transferencia','yape','plin','otro'))");
            DB::statement('ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_monto_positivo CHECK (monto > 0)');
            DB::statement("ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_medio_requerido_en_pago CHECK ((tipo = 'pago' AND medio_pago IS NOT NULL) OR (tipo <> 'pago'))");
            DB::statement("ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_referencia_requerida CHECK ((tipo = 'pago' AND medio_pago <> 'efectivo' AND referencia IS NOT NULL) OR (tipo = 'pago' AND medio_pago = 'efectivo') OR (tipo <> 'pago'))");
            DB::statement("ALTER TABLE movimientos_pago ADD CONSTRAINT movimientos_pago_motivo_requerido CHECK ((tipo IN ('anulacion','devolucion') AND motivo IS NOT NULL) OR (tipo = 'pago'))");
            DB::statement('CREATE UNIQUE INDEX movimientos_pago_referencia_unica_idx ON movimientos_pago (medio_pago, referencia) WHERE referencia IS NOT NULL');
            DB::statement("CREATE UNIQUE INDEX movimientos_pago_pago_unico_idx ON movimientos_pago (obligacion_pago_id) WHERE tipo = 'pago'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS movimientos_pago_pago_unico_idx');
            DB::statement('DROP INDEX IF EXISTS movimientos_pago_referencia_unica_idx');
            DB::statement('DROP INDEX IF EXISTS obligaciones_pago_pendientes_idx');
        }

        Schema::dropIfExists('movimientos_pago');
        Schema::dropIfExists('obligaciones_pago');
        Schema::dropIfExists('beneficios_alumnos');
        Schema::dropIfExists('conceptos_pago');
        Schema::dropIfExists('configuraciones_financieras');
    }
};
