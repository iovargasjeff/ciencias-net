<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IdentityAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $this->demoUser('superadmin@example.test', 'Superadmin Demo', 'superadmin');
        $this->demoUser('gestor@example.test', 'Gestor de Usuarios Demo', 'gestor_usuarios');
        $this->demoUser('toe@example.test', 'TOE Demo', 'toe');
        $this->demoUser('psicologia@example.test', 'Psicologia Demo', 'psicologia');
        $this->demoUser('auxiliar@example.test', 'Auxiliar Demo', 'auxiliar');
        $this->demoUser('administrativo@example.test', 'Administrativo Demo', 'administrativo');

        $coordinator = $this->demoUser('coordinacion@example.test', 'Coordinacion Academica Demo', 'coordinador_academico');
        $teacherUser = $this->demoUser('docente@example.test', 'Docente Demo', 'docente');
        $parentUser = $this->demoUser('padre@example.test', 'Padre Demo', 'padre');
        $studentUser = $this->demoUser('alumno@example.test', 'Alumno Demo', 'alumno');

        $period = PeriodoAcademico::factory()->create(['creado_por' => $coordinator->id]);
        $grade = Grado::create([
            'periodo_academico_id' => $period->id,
            'nombre' => '3ro Secundaria',
            'nivel' => 'Secundaria',
            'orden' => 3,
            'activo' => true,
        ]);
        $section = Seccion::create([
            'grado_id' => $grade->id,
            'nombre' => 'A',
            'turno' => 'manana',
            'activo' => true,
        ]);

        $student = Alumno::create([
            'user_id' => $studentUser->id,
            'dni' => '70000001',
            'nombres' => 'Alumno',
            'apellidos' => 'Demo',
        ]);
        $parent = Padre::create([
            'user_id' => $parentUser->id,
            'dni' => '70000002',
            'nombres' => 'Padre',
            'apellidos' => 'Demo',
            'celular' => '900000001',
            'correo_notificaciones' => 'padre@example.test',
        ]);
        $teacher = Docente::create([
            'user_id' => $teacherUser->id,
            'dni' => '70000003',
            'nombres' => 'Docente',
            'apellidos' => 'Demo',
            'telefono' => '900000002',
        ]);

        DB::table('alumno_padre')->insert([
            'id' => (string) Str::uuid(),
            'alumno_id' => $student->id,
            'padre_id' => $parent->id,
            'relacion' => 'padre',
            'es_contacto_principal' => true,
            'recibe_notificaciones' => true,
        ]);
        Matricula::create([
            'alumno_id' => $student->id,
            'seccion_id' => $section->id,
            'codigo' => 'MAT-DEMO-001',
            'fecha' => now()->toDateString(),
            'estado' => 'activo',
            'registrado_por' => $coordinator->id,
        ]);
        $course = Curso::create([
            'codigo' => 'MAT-001',
            'nombre' => 'Matematica',
            'area' => 'Ciencias',
            'descripcion' => 'Curso demo para desarrollo local.',
            'activo' => true,
        ]);
        CargaAcademica::create([
            'seccion_id' => $section->id,
            'curso_id' => $course->id,
            'docente_id' => $teacher->id,
            'vigente_desde' => now()->startOfYear()->toDateString(),
            'activo' => true,
            'asignado_por' => $coordinator->id,
        ]);
    }

    private function demoUser(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'activo' => true,
            ],
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
