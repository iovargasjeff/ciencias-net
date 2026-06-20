<?php

namespace Tests\Feature;

use App\Modules\Shared\Infrastructure\Models\PrivateFile;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrivateFilesServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $uploader;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'docente', 'alumno'] as $role) {
            Role::create(['name' => $role]);
        }

        $this->uploader = User::factory()->create();
        $this->uploader->assignRole('docente');

        $this->unauthorized = User::factory()->create();
        $this->unauthorized->assignRole('alumno');
    }

    public function test_authorized_user_uploads_private_file_with_checksum(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->createWithContent('lesson.pdf', 'private lesson content');
        $checksum = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->uploader)->post('/api/v1/private-files', [
            'purpose' => 'material',
            'checksum_sha256' => $checksum,
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.purpose', 'material')
            ->assertJsonPath('data.checksum_sha256', $checksum);

        $privateFile = PrivateFile::firstOrFail();
        Storage::disk('private')->assertExists($privateFile->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'private_file.uploaded']);
    }

    public function test_upload_rejects_checksum_mismatch(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->createWithContent('lesson.pdf', 'private lesson content');

        $response = $this->actingAs($this->uploader)->post('/api/v1/private-files', [
            'purpose' => 'material',
            'checksum_sha256' => str_repeat('a', 64),
            'file' => $file,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('private_files', 0);
    }

    public function test_unauthorized_user_cannot_download_private_file(): void
    {
        Storage::fake('private');
        $privateFile = $this->storedPrivateFile('psychology', $this->uploader);

        $response = $this->actingAs($this->unauthorized)->getJson("/api/v1/private-files/{$privateFile->id}/download");

        $response->assertForbidden();
    }

    public function test_temporary_url_expires(): void
    {
        Storage::fake('private');
        $privateFile = $this->storedPrivateFile('material', $this->uploader);

        $response = $this->actingAs($this->uploader)->getJson("/api/v1/private-files/{$privateFile->id}/download?temporary=1");
        $response->assertOk()->assertJsonStructure(['data' => ['url', 'expires_at']]);

        $url = $response->json('data.url');
        $this->get($url)->assertOk();

        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_cleanup_deletes_expired_evidence_and_audits_it(): void
    {
        Storage::fake('private');
        $privateFile = $this->storedPrivateFile('biometric_exception', $this->uploader, now()->subMinute());

        $this->artisan('private-files:delete-expired')
            ->expectsOutput('Deleted 1 expired private file(s).')
            ->assertSuccessful();

        Storage::disk('private')->assertMissing($privateFile->path);
        $this->assertNotNull($privateFile->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'private_file.deleted_by_retention']);
    }

    private function storedPrivateFile(string $purpose, User $uploader, mixed $expiresAt = null): PrivateFile
    {
        $path = "{$purpose}/fixture.pdf";
        Storage::disk('private')->put($path, 'private content');

        return PrivateFile::create([
            'purpose' => $purpose,
            'disk' => 'private',
            'path' => $path,
            'original_name' => 'fixture.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 15,
            'checksum_sha256' => hash('sha256', 'private content'),
            'uploaded_by' => $uploader->id,
            'expires_at' => $expiresAt,
        ]);
    }
}
