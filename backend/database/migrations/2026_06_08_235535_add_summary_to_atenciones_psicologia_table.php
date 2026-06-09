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
        Schema::table('atenciones_psicologia', function (Blueprint $table) {
            $table->text('summary')->after('fecha_atencion');
            $table->text('notas_privadas')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atenciones_psicologia', function (Blueprint $table) {
            $table->dropColumn('summary');
            $table->text('notas_privadas')->nullable(false)->change();
        });
    }
};
