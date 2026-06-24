<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimestres_academicos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestampsTz();
            $table->unique(['periodo_academico_id', 'nombre']);
            $table->index(['periodo_academico_id', 'fecha_inicio', 'fecha_fin']);
        });

        Schema::table('grados', function (Blueprint $table): void {
            $table->string('catalog_code', 40)->nullable()->after('id');
        });

        Schema::table('cursos', function (Blueprint $table): void {
            $table->foreignUuid('grado_id')->nullable()->after('id')->constrained('grados')->nullOnDelete();
            $table->string('nombre_normalizado', 180)->nullable()->after('nombre');
        });

        DB::table('cursos')->whereNull('nombre_normalizado')->orderBy('id')->chunkById(100, function ($courses): void {
            foreach ($courses as $course) {
                DB::table('cursos')->where('id', $course->id)->update([
                    'nombre_normalizado' => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $course->nombre))),
                ]);
            }
        }, 'id');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bimestres_academicos ADD CONSTRAINT bimestres_fechas_validas CHECK (fecha_fin >= fecha_inicio)');
            DB::statement('ALTER TABLE secciones ADD CONSTRAINT secciones_capacidad_positiva CHECK (capacidad > 0)');
            DB::statement('CREATE UNIQUE INDEX cursos_unicos_por_grado_nombre ON cursos (grado_id, nombre_normalizado) WHERE grado_id IS NOT NULL AND deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS cursos_unicos_por_grado_nombre');
        }

        Schema::table('cursos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('grado_id');
            $table->dropColumn('nombre_normalizado');
        });

        Schema::table('grados', function (Blueprint $table): void {
            $table->dropColumn('catalog_code');
        });

        Schema::dropIfExists('bimestres_academicos');
    }
};
