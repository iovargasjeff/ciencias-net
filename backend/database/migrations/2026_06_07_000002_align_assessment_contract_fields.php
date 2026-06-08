<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('examenes')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE examenes DROP CONSTRAINT IF EXISTS examenes_canal_valido');
        }

        Schema::table('examenes', function (Blueprint $table): void {
            if (Schema::hasColumn('examenes', 'periodo_nombre') && ! Schema::hasColumn('examenes', 'assessment_type')) {
                $table->renameColumn('periodo_nombre', 'assessment_type');
            }

            if (Schema::hasColumn('examenes', 'canal') && ! Schema::hasColumn('examenes', 'channel')) {
                $table->renameColumn('canal', 'channel');
            }
        });

        if (Schema::hasColumn('examenes', 'channel')) {
            DB::table('examenes')
                ->where('channel', 'ciencias')
                ->update(['channel' => 'sciences']);

            DB::table('examenes')
                ->where('channel', 'letras')
                ->update(['channel' => 'humanities']);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE examenes ADD CONSTRAINT examenes_channel_valido CHECK (channel IN ('general','sciences','humanities'))");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('examenes')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE examenes DROP CONSTRAINT IF EXISTS examenes_channel_valido');
        }

        if (Schema::hasColumn('examenes', 'channel')) {
            DB::table('examenes')
                ->where('channel', 'sciences')
                ->update(['channel' => 'ciencias']);

            DB::table('examenes')
                ->where('channel', 'humanities')
                ->update(['channel' => 'letras']);
        }

        Schema::table('examenes', function (Blueprint $table): void {
            if (Schema::hasColumn('examenes', 'assessment_type') && ! Schema::hasColumn('examenes', 'periodo_nombre')) {
                $table->renameColumn('assessment_type', 'periodo_nombre');
            }

            if (Schema::hasColumn('examenes', 'channel') && ! Schema::hasColumn('examenes', 'canal')) {
                $table->renameColumn('channel', 'canal');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE examenes ADD CONSTRAINT examenes_canal_valido CHECK (canal IN ('general','ciencias','letras'))");
        }
    }
};
