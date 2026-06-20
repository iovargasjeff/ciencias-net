<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Administrativo;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdentityFamilyRoleRulesRefinementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('gestor_usuarios');
    }

    public function test_account_creation_validates_role_profiles_and_blocks_superadmin(): void
    {
        $this->actingAs($this->manager)->postJson('/api/v1/accounts', [
            'name' => 'Ada Docente',
            'email' => 'ada.docente@example.test',
            'roles' => ['docente'],
            'last_names' => 'Docente',
            'phone' => '999111222',
        ])->assertUnprocessable()->assertJsonPath('error.fields.dni.0', 'Este campo es obligatorio para el rol seleccionado.');

        $teacher = $this->actingAs($this->manager)->postJson('/api/v1/accounts', [
            'name' => 'Ada Docente',
            'email' => 'ada.docente@example.test',
            'roles' => ['docente'],
            'dni' => '11223344',
            'last_names' => 'Docente',
            'phone' => '999111222',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('docentes', ['user_id' => $teacher, 'dni' => '11223344']);

        $staff = $this->actingAs($this->manager)->postJson('/api/v1/accounts', [
            'name' => 'Auxiliar Uno',
            'email' => 'auxiliar.uno@example.test',
            'roles' => ['auxiliar'],
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('administrativos', ['user_id' => $staff, 'cargo' => 'auxiliar']);
        $this->assertSame(1, Docente::where('dni', '11223344')->count());
        $this->assertSame(1, Administrativo::where('user_id', $staff)->count());

        $this->actingAs($this->manager)->postJson('/api/v1/accounts', [
            'name' => 'Root User',
            'email' => 'root@example.test',
            'roles' => ['superadmin'],
        ])->assertUnprocessable()->assertJsonPath('error.fields.roles.0', 'El rol superadmin no puede crearse desde la API ordinaria.');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $target = User::factory()->create();

        $this->actingAs($superadmin)
            ->putJson("/api/v1/accounts/{$target->id}/roles", ['roles' => ['superadmin']])
            ->assertForbidden();
    }

    public function test_session_profile_includes_effective_permissions(): void
    {
        $this->actingAs($this->manager)->getJson('/api/v1/auth/session')
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'gestor_usuarios')
            ->assertJsonPath('data.permissions.0', 'gestionar_usuarios');
    }

    public function test_family_links_filter_by_grade_and_search_and_store_flags(): void
    {
        [$period, $grade, $section] = $this->academicSection('Quinto', 'A');
        [, $otherGrade, $otherSection] = $this->academicSection('Cuarto', 'B', $period);
        $parent = Padre::factory()->create(['dni' => '12345678', 'apellidos' => 'Vargas']);
        $parent->user->assignRole('padre');
        $student = Alumno::factory()->create(['dni' => '87654321', 'nombres' => 'Lucia', 'apellidos' => 'Filtro']);
        $otherStudent = Alumno::factory()->create(['dni' => '87654322', 'nombres' => 'Mario', 'apellidos' => 'Otro']);

        Matricula::create([
            'alumno_id' => $student->id,
            'seccion_id' => $section->id,
            'codigo' => 'MAT-'.Str::random(8),
            'fecha' => '2026-03-01',
            'estado' => 'activo',
            'registrado_por' => $this->manager->id,
        ]);
        Matricula::create([
            'alumno_id' => $otherStudent->id,
            'seccion_id' => $otherSection->id,
            'codigo' => 'MAT-'.Str::random(8),
            'fecha' => '2026-03-01',
            'estado' => 'activo',
            'registrado_por' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)->postJson('/api/v1/family-links', [
            'parent_account_id' => $parent->user_id,
            'student_id' => $student->id,
            'relationship' => 'madre',
            'is_primary_contact' => true,
            'receives_notifications' => false,
        ])->assertCreated()->assertJsonPath('data.relationship', 'madre');

        $this->actingAs($this->manager)->postJson('/api/v1/family-links', [
            'parent_account_id' => $parent->user_id,
            'student_id' => $otherStudent->id,
            'relationship' => 'apoderado',
        ])->assertCreated();

        $this->assertDatabaseHas('alumno_padre', [
            'alumno_id' => $student->id,
            'padre_id' => $parent->id,
            'es_contacto_principal' => true,
            'recibe_notificaciones' => false,
        ]);

        $this->actingAs($this->manager)->getJson("/api/v1/family-links?grade_id={$grade->id}&search=Lucia")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_name', 'Lucia Filtro');

        $this->actingAs($this->manager)->getJson('/api/v1/search/parents?search=Vargas')
            ->assertOk()
            ->assertJsonPath('data.0.dni', '12345678');
    }

    public function test_announcements_are_visible_only_to_real_section_recipients(): void
    {
        Queue::fake();
        [, $grade, $sectionA] = $this->academicSection('Quinto', 'A');
        $sectionB = Seccion::create([
            'grado_id' => $grade->id,
            'nombre' => 'B',
            'turno' => 'manana',
            'capacidad' => 30,
            'activo' => true,
        ]);
        $parentA = $this->linkedParentInSection($sectionA, '11111111');
        $parentB = $this->linkedParentInSection($sectionB, '22222222');

        $this->actingAs(User::role('superadmin')->first() ?? $this->superadmin())->postJson('/api/v1/announcements', [
            'title' => 'Solo Quinto A',
            'body' => 'Mensaje segmentado',
            'audience_type' => 'sections',
            'audience_ids' => [$sectionA->id],
        ])->assertCreated();

        $this->actingAs($parentA->user)->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.titulo', 'Solo Quinto A');

        $this->actingAs($parentB->user)->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_parent_account_statement_uses_all_linked_children_without_mock_data(): void
    {
        $parent = $this->linkedParentInSection($this->academicSection('Quinto', 'A')[2], '33333333');
        $period = PeriodoAcademico::factory()->create(['creado_por' => $this->manager->id]);
        $concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $period->id,
            'creado_por' => $this->manager->id,
            'estado' => 'vigente',
        ]);
        $student = $parent->alumnos()->firstOrFail();
        ObligacionPago::factory()->create([
            'alumno_id' => $student->id,
            'concepto_id' => $concept->id,
            'registrado_por' => $this->manager->id,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($parent->user)->getJson('/api/v1/account-statements')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student.id', $student->id);
    }

    private function academicSection(string $gradeName, string $sectionName, ?PeriodoAcademico $period = null): array
    {
        $period ??= PeriodoAcademico::factory()->create(['creado_por' => $this->manager->id]);
        $grade = Grado::create([
            'periodo_academico_id' => $period->id,
            'nombre' => $gradeName,
            'nivel' => 'secundaria',
            'orden' => $gradeName === 'Quinto' ? 5 : 4,
            'activo' => true,
        ]);
        $section = Seccion::create([
            'grado_id' => $grade->id,
            'nombre' => $sectionName,
            'turno' => 'manana',
            'capacidad' => 30,
            'activo' => true,
        ]);

        return [$period, $grade, $section];
    }

    private function linkedParentInSection(Seccion $section, string $dni): Padre
    {
        $parent = Padre::factory()->create(['dni' => $dni]);
        $parent->user->assignRole('padre');
        $student = Alumno::factory()->create();
        $student->user?->assignRole('alumno');
        Matricula::create([
            'alumno_id' => $student->id,
            'seccion_id' => $section->id,
            'codigo' => 'MAT-'.Str::random(8),
            'fecha' => '2026-03-01',
            'estado' => 'activo',
            'registrado_por' => $this->manager->id,
        ]);
        DB::table('alumno_padre')->insert([
            'id' => (string) Str::uuid(),
            'alumno_id' => $student->id,
            'padre_id' => $parent->id,
            'relacion' => 'padre',
            'es_contacto_principal' => true,
            'recibe_notificaciones' => true,
        ]);

        return $parent;
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
