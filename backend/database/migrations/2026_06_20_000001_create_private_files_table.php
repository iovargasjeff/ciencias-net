<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('purpose', 40);
            $table->string('disk', 40);
            $table->string('path', 700)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64);
            $table->jsonb('metadata')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();

            $table->index(['purpose', 'created_at']);
            $table->index(['expires_at', 'deleted_at']);
            $table->index('uploaded_by');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE private_files ADD CONSTRAINT private_files_purpose_check CHECK (purpose IN ('material', 'incident_evidence', 'psychology', 'biometric_exception', 'report', 'other'))");
            DB::statement('ALTER TABLE private_files ADD CONSTRAINT private_files_size_check CHECK (size_bytes > 0)');
            DB::statement("ALTER TABLE private_files ADD CONSTRAINT private_files_checksum_check CHECK (checksum_sha256 ~ '^[a-f0-9]{64}$')");
            DB::statement("ALTER TABLE private_files ADD CONSTRAINT private_files_biometric_expiry_check CHECK (purpose <> 'biometric_exception' OR expires_at IS NOT NULL)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('private_files');
    }
};
