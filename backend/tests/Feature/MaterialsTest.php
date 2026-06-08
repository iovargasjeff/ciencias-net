<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Materiales\Infrastructure\Models\Material;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaterialsTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;

    private User $alumno;

    private User $otroAlumno;

    private CargaAcademica $carga;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'alumno']);

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');

        $this->alumno = User::factory()->create();
        $this->alumno->assignRole('alumno');

        $this->otroAlumno = User::factory()->create();
        $this->otroAlumno->assignRole('alumno');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $periodo = PeriodoAcademico::create([
            'nombre' => '2026', 'tipo' => 'regular', 'estado' => 'activo', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(10),
            'creado_por' => $superadmin->id,
        ]);

        $grado = Grado::create(['periodo_academico_id' => $periodo->id, 'nombre' => '1ro Secundaria', 'nivel' => 'secundaria', 'orden' => 1]);
        $seccion = Seccion::create(['grado_id' => $grado->id, 'nombre' => 'A', 'turno' => 'mañana']);
        $curso = Curso::create(['codigo' => 'MAT1', 'nombre' => 'Matemáticas', 'area' => 'ciencias', 'grado_id' => $grado->id]);

        $docenteId = Str::uuid();
        DB::table('docentes')->insert(['id' => $docenteId, 'user_id' => $this->docente->id, 'dni' => '12345678', 'nombres' => 'D', 'apellidos' => '1']);

        $this->carga = CargaAcademica::create([
            'periodo_academico_id' => $periodo->id,
            'curso_id' => $curso->id,
            'seccion_id' => $seccion->id,
            'docente_id' => $docenteId,
            'asignado_por' => $superadmin->id,
            'vigente_desde' => now(),
        ]);

        $alumnoId = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumnoId, 'user_id' => $this->alumno->id, 'dni' => '111', 'nombres' => 'A', 'apellidos' => '1']);

        $otroAlumnoId = Str::uuid();
        DB::table('alumnos')->insert(['id' => $otroAlumnoId, 'user_id' => $this->otroAlumno->id, 'dni' => '222', 'nombres' => 'A', 'apellidos' => '2']);

        Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumnoId,
            'codigo' => 'MAT001', 'fecha' => now(), 'registrado_por' => $superadmin->id, 'estado' => 'activa',
        ]);

        // Otro alumno inactivo en la sección
        Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $otroAlumnoId,
            'codigo' => 'MAT002', 'fecha' => now(), 'registrado_por' => $superadmin->id, 'estado' => 'retirada',
        ]);
    }

    public function test_docente_can_upload_material()
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->docente)->postJson('/api/v1/materials', [
            'carga_academica_id' => $this->carga->id,
            'titulo' => 'Semana 1 - Teoría',
            'semana' => 1,
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('materiales', [
            'titulo' => 'Semana 1 - Teoría',
            'carga_academica_id' => $this->carga->id,
            'tipo' => 'archivo',
        ]);

        $material = Material::first();
        Storage::disk('private')->assertExists($material->ruta_o_url);
    }

    public function test_rejects_invalid_file_extension()
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('script.php', 1000, 'application/x-httpd-php');

        $response = $this->actingAs($this->docente)->postJson('/api/v1/materials', [
            'carga_academica_id' => $this->carga->id,
            'titulo' => 'Malicious Script',
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_student_can_download_material()
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $path = $file->storeAs("materials/{$this->carga->id}", 'test.pdf', 'private');

        $material = Material::create([
            'titulo' => 'Doc',
            'tipo' => 'archivo',
            'ruta_o_url' => $path,
            'carga_academica_id' => $this->carga->id,
            'subido_por' => $this->docente->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->alumno)->get("/api/v1/materials/{$material->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_inactive_student_cannot_download()
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $path = $file->storeAs("materials/{$this->carga->id}", 'test.pdf', 'private');

        $material = Material::create([
            'titulo' => 'Doc',
            'tipo' => 'archivo',
            'ruta_o_url' => $path,
            'carga_academica_id' => $this->carga->id,
            'subido_por' => $this->docente->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->otroAlumno)->getJson("/api/v1/materials/{$material->id}/download");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_private_storage_directly()
    {
        $response = $this->get('/storage/private/materials/anything.pdf');
        // This should return 404 or 403 since private disk is not exposed
        $response->assertStatus(403);
    }
}
