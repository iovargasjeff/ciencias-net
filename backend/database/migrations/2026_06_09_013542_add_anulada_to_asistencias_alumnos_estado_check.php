<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE asistencias_alumnos DROP CONSTRAINT asistencias_alumnos_estado_check');
            DB::statement("ALTER TABLE asistencias_alumnos ADD CONSTRAINT asistencias_alumnos_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta_injustificada', 'falta_justificada', 'anulada'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE asistencias_alumnos SET estado = 'falta_injustificada' WHERE estado = 'anulada'");
            DB::statement('ALTER TABLE asistencias_alumnos DROP CONSTRAINT asistencias_alumnos_estado_check');
            DB::statement("ALTER TABLE asistencias_alumnos ADD CONSTRAINT asistencias_alumnos_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta_injustificada', 'falta_justificada'))");
        }
    }
};
