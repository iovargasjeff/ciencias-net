<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table): void {
            if (! Schema::hasColumn('cursos', 'grado_id')) {
                $table->foreignUuid('grado_id')->nullable()->after('id')->constrained('grados')->nullOnDelete();
                $table->index(['grado_id', 'activo']);
            }
        });

        DB::statement(<<<'SQL'
            UPDATE cursos
            SET grado_id = source.grado_id
            FROM (
                SELECT DISTINCT ON (ca.curso_id) ca.curso_id, s.grado_id
                FROM carga_academica ca
                INNER JOIN secciones s ON s.id = ca.seccion_id
                WHERE ca.deleted_at IS NULL
                ORDER BY ca.curso_id, ca.created_at DESC
            ) source
            WHERE cursos.id = source.curso_id
              AND cursos.grado_id IS NULL
        SQL);

        Schema::create('matricula_carga_academica', function (Blueprint $table): void {
            $table->foreignUuid('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignUuid('carga_academica_id')->constrained('carga_academica')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['matricula_id', 'carga_academica_id']);
            $table->index('carga_academica_id');
        });

        DB::statement(<<<'SQL'
            INSERT INTO matricula_carga_academica (matricula_id, carga_academica_id)
            SELECT m.id, ca.id
            FROM matriculas m
            INNER JOIN carga_academica ca ON ca.seccion_id = m.seccion_id
            WHERE m.deleted_at IS NULL
              AND ca.deleted_at IS NULL
              AND ca.activo = true
            ON CONFLICT DO NOTHING
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('matricula_carga_academica');

        Schema::table('cursos', function (Blueprint $table): void {
            if (Schema::hasColumn('cursos', 'grado_id')) {
                $table->dropConstrainedForeignId('grado_id');
            }
        });
    }
};
