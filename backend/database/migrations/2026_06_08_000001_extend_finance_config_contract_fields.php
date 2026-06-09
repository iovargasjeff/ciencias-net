<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conceptos_pago', function (Blueprint $table): void {
            $table->string('codigo', 30)->nullable()->after('id');
            $table->date('vigente_desde')->nullable()->after('bloqueado_en');
            $table->date('vigente_hasta')->nullable()->after('vigente_desde');
            $table->foreignUuid('reemplaza_concepto_id')
                ->nullable()
                ->after('vigente_hasta')
                ->constrained('conceptos_pago')
                ->nullOnDelete();
        });

        DB::table('conceptos_pago')
            ->whereNull('codigo')
            ->orderBy('created_at')
            ->select(['id', 'nombre'])
            ->get()
            ->each(function (object $concept): void {
                DB::table('conceptos_pago')
                    ->where('id', $concept->id)
                    ->update([
                        'codigo' => 'LEGACY-'.substr((string) $concept->id, 0, 8),
                        'vigente_desde' => now()->toDateString(),
                    ]);
            });

        Schema::create('beneficio_concepto', function (Blueprint $table): void {
            $table->foreignUuid('beneficio_id')
                ->constrained('beneficios_alumnos')
                ->cascadeOnDelete();
            $table->foreignUuid('concepto_id')
                ->constrained('conceptos_pago')
                ->cascadeOnDelete();
            $table->timestampsTz();

            $table->primary(['beneficio_id', 'concepto_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX conceptos_pago_codigo_activo_periodo_idx ON conceptos_pago (periodo_academico_id, codigo) WHERE vigente_hasta IS NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS conceptos_pago_codigo_activo_periodo_idx');
        }

        Schema::dropIfExists('beneficio_concepto');

        Schema::table('conceptos_pago', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reemplaza_concepto_id');
            $table->dropColumn(['codigo', 'vigente_desde', 'vigente_hasta']);
        });
    }
};
