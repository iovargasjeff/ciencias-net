<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimientos_biometricos', function (Blueprint $table): void {
            $table->text('fundamento_legal')->nullable()->after('documento_version');
            $table->timestampTz('expira_en')->nullable()->after('otorgado_en');
        });
    }

    public function down(): void
    {
        Schema::table('consentimientos_biometricos', function (Blueprint $table): void {
            $table->dropColumn(['fundamento_legal', 'expira_en']);
        });
    }
};
