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
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE asistencias_alumnos DROP CONSTRAINT asistencias_alumnos_estado_check');
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE asistencias_alumnos ADD CONSTRAINT asistencias_alumnos_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta_injustificada', 'falta_justificada', 'anulada'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("UPDATE asistencias_alumnos SET estado = 'falta_injustificada' WHERE estado = 'anulada'");
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE asistencias_alumnos DROP CONSTRAINT asistencias_alumnos_estado_check');
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE asistencias_alumnos ADD CONSTRAINT asistencias_alumnos_estado_check CHECK (estado IN ('presente', 'tardanza', 'falta_injustificada', 'falta_justificada'))");
        }
    }
};
