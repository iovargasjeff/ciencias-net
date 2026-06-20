<?php

namespace Database\Seeders;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\Nota;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoCompleteSeeder
 *
 * Genera datos realistas para demostrar TODOS los módulos del sistema:
 *  - Usuarios (superadmin, coordinador, docentes, alumnos, padres, staff)
 *  - Período académico 2026, grados y secciones
 *  - Cursos y carga académica
 *  - Matrículas
 *  - Horarios semanales por curso/sección
 *  - Exámenes y notas
 *  - Asistencias de alumnos y docentes
 *  - Eventos de calendario
 *  - Comunicados
 *  - Finanzas (conceptos, obligaciones, pagos)
 *  - Incidencias y atenciones psicológicas
 */
class DemoCompleteSeeder extends Seeder
{
    // -------------------------------------------------------------------------
    // Datos maestros para referencias rápidas
    // -------------------------------------------------------------------------
    private array $docentes = [];

    private array $alumnos = [];

    private array $padres = [];

    private array $matriculas = [];

    private array $cargas = [];

    private ?string $coordinadorUserId = null;

    private ?string $auxiliarUserId = null;

    private ?string $psicologiaUserId = null;

    public function run(): void
    {
        // ── 1. Usuarios staff ───────────────────────────────────────────────
        $this->seedStaff();

        // ── 2. Período académico ─────────────────────────────────────────────
        $period = $this->seedPeriod();

        // ── 3. Grados + Secciones ────────────────────────────────────────────
        [$gradosPrim, $gradosSec] = $this->seedGrados($period);

        // ── 4. Docentes ──────────────────────────────────────────────────────
        $this->seedDocentes();

        // ── 5. Cursos + Carga académica ──────────────────────────────────────
        $this->seedCursosYCarga($gradosPrim, $gradosSec);

        // ── 6. Alumnos y Padres ──────────────────────────────────────────────
        $this->seedAlumnosYPadres($gradosPrim, $gradosSec);

        // ── 7. Horarios ──────────────────────────────────────────────────────
        $this->seedHorarios();

        // ── 8. Exámenes y Notas ──────────────────────────────────────────────
        $this->seedExamenesYNotas();

        // ── 9. Asistencias ───────────────────────────────────────────────────
        $this->seedAsistencias();

        // ── 10. Eventos de calendario ────────────────────────────────────────
        $this->seedEventosCalendario($period);

        // ── 11. Comunicados ──────────────────────────────────────────────────
        $this->seedComunicados();

        // ── 12. Finanzas ─────────────────────────────────────────────────────
        $this->seedFinanzas($period);

        // ── 13. Incidencias y Psicología ─────────────────────────────────────
        $this->seedIncidenciasYPsicologia();
    }

    // =========================================================================
    // 1. STAFF
    // =========================================================================
    private function seedStaff(): void
    {
        $coordinator = $this->demoUser('coordinador@ciencias.test', 'María Elena Torres Vásquez', 'coordinador_academico');
        $this->coordinadorUserId = $coordinator->id;

        $this->demoUser('superadmin@ciencias.test', 'Carlos Ramos Herrera', 'superadmin');
        $this->demoUser('gestor@ciencias.test', 'Ana Lucía Mendoza Paredes', 'gestor_usuarios');

        $toe = $this->demoUser('toe@ciencias.test', 'Rosa Isabel Flores Quispe', 'toe');
        $this->demoUser('psicologia@ciencias.test', 'Silvia Patricia Rojas Llanos', 'psicologia');
        $this->psicologiaUserId = User::where('email', 'psicologia@ciencias.test')->first()->id;

        $aux = $this->demoUser('auxiliar@ciencias.test', 'Pedro Antonio Salinas Cruz', 'auxiliar');
        $this->auxiliarUserId = $aux->id;

        $this->demoUser('administrativo@ciencias.test', 'Jorge Luis Huanca Mamani', 'administrativo');
    }

    // =========================================================================
    // 2. PERÍODO ACADÉMICO
    // =========================================================================
    private function seedPeriod(): PeriodoAcademico
    {
        return PeriodoAcademico::updateOrCreate(
            ['nombre' => 'Año Escolar 2026'],
            [
                'tipo' => 'anual',
                'fecha_inicio' => '2026-03-09',
                'fecha_fin' => '2026-12-18',
                'estado' => 'activo',
                'creado_por' => $this->coordinadorUserId,
            ]
        );
    }

    // =========================================================================
    // 3. GRADOS Y SECCIONES
    // =========================================================================
    private function seedGrados(PeriodoAcademico $period): array
    {
        $gradosPrim = [];
        $gradosSec = [];

        $gradosData = [
            ['nombre' => '1ro Primaria',   'nivel' => 'Primaria',   'orden' => 1],
            ['nombre' => '3ro Primaria',   'nivel' => 'Primaria',   'orden' => 3],
            ['nombre' => '5to Primaria',   'nivel' => 'Primaria',   'orden' => 5],
            ['nombre' => '1ro Secundaria', 'nivel' => 'Secundaria', 'orden' => 1],
            ['nombre' => '3ro Secundaria', 'nivel' => 'Secundaria', 'orden' => 3],
            ['nombre' => '5to Secundaria', 'nivel' => 'Secundaria', 'orden' => 5],
        ];

        foreach ($gradosData as $gd) {
            $grado = Grado::updateOrCreate(
                ['periodo_academico_id' => $period->id, 'nombre' => $gd['nombre']],
                ['nivel' => $gd['nivel'], 'orden' => $gd['orden'], 'activo' => true]
            );

            $turnos = ['manana', 'tarde'];
            $nombres = ['A', 'B'];
            $aulas = ['Aula 101', 'Aula 102', 'Aula 201', 'Aula 202', 'Aula 301', 'Aula 302'];

            foreach ($nombres as $i => $nombre) {
                Seccion::updateOrCreate(
                    ['grado_id' => $grado->id, 'nombre' => $nombre],
                    ['turno' => $turnos[$i], 'aula' => $aulas[array_rand($aulas)], 'activo' => true]
                );
            }

            if ($gd['nivel'] === 'Primaria') {
                $gradosPrim[] = $grado;
            } else {
                $gradosSec[] = $grado;
            }
        }

        return [$gradosPrim, $gradosSec];
    }

    // =========================================================================
    // 4. DOCENTES
    // =========================================================================
    private function seedDocentes(): void
    {
        $docentesData = [
            ['70100001', 'Roberto',  'Quispe Mamani',   '987654321', 'docente1@ciencias.test'],
            ['70100002', 'Carmen',   'Reyes Atahuaman', '987654322', 'docente2@ciencias.test'],
            ['70100003', 'Luis',     'Ccori Huanca',    '987654323', 'docente3@ciencias.test'],
            ['70100004', 'Patricia', 'Vargas Condori',  '987654324', 'docente4@ciencias.test'],
            ['70100005', 'Miguel',   'Apaza Huayta',    '987654325', 'docente5@ciencias.test'],
            ['70100006', 'Sandra',   'Lazo Cutipa',     '987654326', 'docente6@ciencias.test'],
        ];

        foreach ($docentesData as [$dni, $nombres, $apellidos, $tel, $email]) {
            $user = $this->demoUser($email, "$nombres $apellidos", 'docente');
            $docente = Docente::updateOrCreate(
                ['dni' => $dni],
                ['user_id' => $user->id, 'nombres' => $nombres, 'apellidos' => $apellidos, 'telefono' => $tel]
            );
            $this->docentes[] = $docente;
        }
    }

    // =========================================================================
    // 5. CURSOS Y CARGA ACADÉMICA
    // =========================================================================
    private function seedCursosYCarga(array $gradosPrim, array $gradosSec): void
    {
        $cursosPrim = [
            ['MAT-P', 'Matemática',          'Ciencias'],
            ['COM-P', 'Comunicación',         'Letras'],
            ['CTA-P', 'Ciencia y Tecnología', 'Ciencias'],
            ['ART-P', 'Arte y Cultura',       'Humanidades'],
        ];

        $cursosSec = [
            ['MAT-S', 'Matemática',    'Ciencias'],
            ['COM-S', 'Comunicación',  'Letras'],
            ['CTA-S', 'CTA',           'Ciencias'],
            ['HGE-S', 'Historia, Geografía y Economía', 'Letras'],
            ['ING-S', 'Inglés',        'Idiomas'],
            ['FCC-S', 'Formación Ciudadana', 'Humanidades'],
        ];

        $docenteIdx = 0;

        foreach ($gradosPrim as $grado) {
            foreach ($grado->secciones as $seccion) {
                foreach ($cursosPrim as [$cod, $nom, $area]) {
                    $curso = Curso::updateOrCreate(
                        ['codigo' => $cod.'-'.str_replace(' ', '', $grado->nombre)],
                        ['nombre' => $nom, 'area' => $area, 'activo' => true]
                    );
                    $docente = $this->docentes[$docenteIdx % count($this->docentes)];
                    $carga = CargaAcademica::updateOrCreate(
                        ['seccion_id' => $seccion->id, 'curso_id' => $curso->id, 'docente_id' => $docente->id],
                        [
                            'vigente_desde' => '2026-03-09',
                            'activo' => true,
                            'asignado_por' => $this->coordinadorUserId,
                        ]
                    );
                    $this->cargas[] = $carga;
                    $docenteIdx++;
                }
            }
        }

        foreach ($gradosSec as $grado) {
            foreach ($grado->secciones as $seccion) {
                foreach ($cursosSec as [$cod, $nom, $area]) {
                    $curso = Curso::updateOrCreate(
                        ['codigo' => $cod.'-'.str_replace(' ', '', $grado->nombre)],
                        ['nombre' => $nom, 'area' => $area, 'activo' => true]
                    );
                    $docente = $this->docentes[$docenteIdx % count($this->docentes)];
                    $carga = CargaAcademica::updateOrCreate(
                        ['seccion_id' => $seccion->id, 'curso_id' => $curso->id, 'docente_id' => $docente->id],
                        [
                            'vigente_desde' => '2026-03-09',
                            'activo' => true,
                            'asignado_por' => $this->coordinadorUserId,
                        ]
                    );
                    $this->cargas[] = $carga;
                    $docenteIdx++;
                }
            }
        }
    }

    // =========================================================================
    // 6. ALUMNOS Y PADRES
    // =========================================================================
    private function seedAlumnosYPadres(array $gradosPrim, array $gradosSec): void
    {
        // Nombres y apellidos realistas peruanos
        $nombresV = ['Alejandro', 'Bryan', 'Carlos', 'Diego', 'Emilio', 'Fernando', 'Gonzalo', 'Hector', 'Ivan', 'Juan'];
        $nombresM = ['Alejandra', 'Brenda', 'Camila', 'Diana', 'Elena', 'Fernanda', 'Gabriela', 'Heidi', 'Iris', 'Juana'];
        $apellidos = ['Mamani', 'Quispe', 'Huanca', 'Condori', 'Flores', 'García', 'López', 'Torres', 'Pérez', 'Chávez'];

        $dniBases = [70200001, 70200030, 70200060, 70200090, 70200120, 70200150];
        $padresDni = [70300001, 70300030, 70300060, 70300090, 70300120, 70300150];

        $allSecciones = [];
        foreach (array_merge($gradosPrim, $gradosSec) as $grado) {
            foreach ($grado->secciones as $sec) {
                $allSecciones[] = $sec;
            }
        }

        $alumnoCounter = 0;

        foreach ($allSecciones as $secIdx => $seccion) {
            // 5 alumnos por sección
            for ($i = 0; $i < 5; $i++) {
                $dniAlumno = str_pad($dniBases[$secIdx % count($dniBases)] + $i, 8, '0', STR_PAD_LEFT);
                $dniPadre = str_pad($padresDni[$secIdx % count($padresDni)] + $i, 8, '0', STR_PAD_LEFT);

                // Evitar duplicados
                if (Alumno::where('dni', $dniAlumno)->exists()) {
                    $alumnoCounter++;

                    continue;
                }

                $esVaron = ($i % 2 === 0);
                $nombre = $esVaron ? $nombresV[$i % count($nombresV)] : $nombresM[$i % count($nombresM)];
                $apellido1 = $apellidos[($secIdx + $i) % count($apellidos)];
                $apellido2 = $apellidos[($secIdx + $i + 1) % count($apellidos)];

                $emailAlumno = 'alumno'.str_pad($alumnoCounter + 1, 3, '0', STR_PAD_LEFT).'@ciencias.test';
                $userAlumno = $this->demoUser($emailAlumno, "$nombre $apellido1 $apellido2", 'alumno');

                $alumno = Alumno::create([
                    'user_id' => $userAlumno->id,
                    'dni' => $dniAlumno,
                    'nombres' => $nombre,
                    'apellidos' => "$apellido1 $apellido2",
                ]);

                // Padre/madre
                if (! Padre::where('dni', $dniPadre)->exists()) {
                    $emailPadre = 'padre'.str_pad($alumnoCounter + 1, 3, '0', STR_PAD_LEFT).'@ciencias.test';
                    $userPadre = $this->demoUser($emailPadre, "Padre de $nombre $apellido1", 'padre');
                    $padre = Padre::create([
                        'user_id' => $userPadre->id,
                        'dni' => $dniPadre,
                        'nombres' => 'Juan',
                        'apellidos' => $apellido1,
                        'celular' => '9'.str_pad($alumnoCounter + 1, 8, '0', STR_PAD_LEFT),
                        'correo_notificaciones' => $emailPadre,
                    ]);

                    DB::table('alumno_padre')->insert([
                        'id' => (string) Str::uuid(),
                        'alumno_id' => $alumno->id,
                        'padre_id' => $padre->id,
                        'relacion' => 'padre',
                        'es_contacto_principal' => true,
                        'recibe_notificaciones' => true,
                    ]);

                    $this->padres[] = $padre;
                }

                // Matrícula
                $codigoMat = 'MAT-2026-'.str_pad($alumnoCounter + 1, 5, '0', STR_PAD_LEFT);
                $matricula = Matricula::updateOrCreate(
                    ['alumno_id' => $alumno->id, 'seccion_id' => $seccion->id],
                    [
                        'codigo' => $codigoMat,
                        'fecha' => '2026-03-01',
                        'estado' => 'activo',
                        'registrado_por' => $this->coordinadorUserId,
                    ]
                );

                $this->alumnos[] = $alumno;
                $this->matriculas[] = $matricula;
                $alumnoCounter++;
            }
        }
    }

    // =========================================================================
    // 7. HORARIOS
    // =========================================================================
    private function seedHorarios(): void
    {
        $bloques = [
            // [dia_semana, hora_inicio, hora_fin, aula]
            [1, '07:30', '08:15', 'Aula 101'],
            [1, '08:15', '09:00', 'Aula 101'],
            [2, '07:30', '08:15', 'Aula 102'],
            [2, '08:15', '09:00', 'Aula 102'],
            [3, '07:30', '08:15', 'Aula 201'],
            [3, '10:00', '10:45', 'Aula 201'],
            [4, '07:30', '08:15', 'Aula 202'],
            [5, '07:30', '08:15', 'Aula 301'],
        ];

        foreach ($this->cargas as $idx => $carga) {
            $bloque = $bloques[$idx % count($bloques)];
            Horario::updateOrCreate(
                ['carga_academica_id' => $carga->id, 'dia_semana' => $bloque[0]],
                ['hora_inicio' => $bloque[1], 'hora_fin' => $bloque[2], 'aula' => $bloque[3]]
            );
        }
    }

    // =========================================================================
    // 8. EXÁMENES Y NOTAS
    // =========================================================================
    private function seedExamenesYNotas(): void
    {
        // Tomar las primeras 6 cargas para no generar demasiados datos
        $cargasMuestra = array_slice($this->cargas, 0, 6);

        foreach ($cargasMuestra as $carga) {
            // Examen bimestre 1
            $examen1 = Examen::updateOrCreate(
                ['carga_academica_id' => $carga->id, 'titulo' => 'Examen Bimestral I'],
                [
                    'fecha_aplicacion' => '2026-05-15',
                    'assessment_type' => 'I Bimestre',
                    'channel' => 'general',
                    'total_preguntas' => 20,
                    'puntaje_maximo' => 20.00,
                    'estado' => 'cerrado',
                    'publicado_por' => $this->coordinadorUserId,
                    'publicado_en' => now()->subDays(20)->toDateTimeString(),
                ]
            );

            // Examen bimestre 2
            $examen2 = Examen::updateOrCreate(
                ['carga_academica_id' => $carga->id, 'titulo' => 'Examen Bimestral II'],
                [
                    'fecha_aplicacion' => '2026-07-10',
                    'assessment_type' => 'II Bimestre',
                    'channel' => 'general',
                    'total_preguntas' => 25,
                    'puntaje_maximo' => 20.00,
                    'estado' => 'publicado',
                    'publicado_por' => $this->coordinadorUserId,
                    'publicado_en' => now()->subDays(5)->toDateTimeString(),
                ]
            );

            // Notas para los alumnos matriculados en la sección de esta carga
            $matriculasDeSec = Matricula::where('seccion_id', $carga->seccion_id)->get();

            $puntajes1 = [18.5, 14.0, 17.0, 11.5, 20.0];
            $puntajes2 = [16.0, 15.5, 19.0, 12.0, 18.0];

            foreach ($matriculasDeSec as $idx => $mat) {
                Nota::updateOrCreate(
                    ['examen_id' => $examen1->id, 'matricula_id' => $mat->id],
                    [
                        'puntaje' => $puntajes1[$idx % count($puntajes1)],
                        'estado' => 'registrada',
                        'puesto_ranking' => $idx + 1,
                        'registrado_por' => $this->coordinadorUserId,
                    ]
                );

                Nota::updateOrCreate(
                    ['examen_id' => $examen2->id, 'matricula_id' => $mat->id],
                    [
                        'puntaje' => $puntajes2[$idx % count($puntajes2)],
                        'estado' => 'registrada',
                        'puesto_ranking' => $idx + 1,
                        'registrado_por' => $this->coordinadorUserId,
                    ]
                );
            }
        }
    }

    // =========================================================================
    // 9. ASISTENCIAS
    // =========================================================================
    private function seedAsistencias(): void
    {
        // Últimos 10 días hábiles
        $fechas = [];
        $d = now()->subDays(14);
        while (count($fechas) < 10) {
            if (! in_array($d->dayOfWeek, [0, 6])) { // omitir fin de semana
                $fechas[] = $d->toDateString();
            }
            $d->addDay();
        }

        $estadosAlumno = ['presente', 'presente', 'presente', 'tardanza', 'falta_injustificada'];
        $estadosDocente = ['presente', 'presente', 'presente', 'presente', 'falta_justificada'];

        foreach (array_slice($this->alumnos, 0, 20) as $idx => $alumno) {
            foreach ($fechas as $fecha) {
                $estado = $estadosAlumno[$idx % count($estadosAlumno)];
                $asistId = (string) Str::uuid();

                DB::table('asistencias_alumnos')->updateOrInsert(
                    ['alumno_id' => $alumno->id, 'fecha' => $fecha],
                    [
                        'id' => $asistId,
                        'alumno_id' => $alumno->id,
                        'fecha' => $fecha,
                        'primer_ingreso' => $estado !== 'falta_injustificada' ? '07:35:00' : null,
                        'ultima_salida' => $estado !== 'falta_injustificada' ? '13:30:00' : null,
                        'estado' => $estado,
                        'presencia_abierta' => false,
                        'registrado_por' => $this->auxiliarUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        foreach (array_slice($this->docentes, 0, 4) as $idx => $docente) {
            foreach ($fechas as $fecha) {
                $estado = $estadosDocente[$idx % count($estadosDocente)];
                $tardanza = ($estado === 'presente') ? rand(0, 10) : 0;

                DB::table('asistencias_docentes')->updateOrInsert(
                    ['docente_id' => $docente->id, 'fecha' => $fecha],
                    [
                        'id' => (string) Str::uuid(),
                        'docente_id' => $docente->id,
                        'fecha' => $fecha,
                        'primer_ingreso' => $estado !== 'falta_justificada' ? '07:30:00' : null,
                        'ultima_salida' => $estado !== 'falta_justificada' ? '13:30:00' : null,
                        'estado' => $estado,
                        'minutos_tardanza' => $tardanza,
                        'docente_sustituto_id' => null,
                        'registrado_por' => $this->auxiliarUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    // =========================================================================
    // 10. EVENTOS DE CALENDARIO
    // =========================================================================
    private function seedEventosCalendario(PeriodoAcademico $period): void
    {
        $eventos = [
            ['evento',      'Día del Maestro',                    '2026-07-06 08:00:00', '2026-07-06 17:00:00'],
            ['no_laboral',  'Fiestas Patrias',                    '2026-07-28 00:00:00', '2026-07-29 23:59:00'],
            ['examen',      'Examen Bimestral I',                  '2026-05-15 08:00:00', '2026-05-15 12:00:00'],
            ['simulacro',   'Simulacro ECE Primaria',              '2026-09-05 08:00:00', '2026-09-05 13:00:00'],
            ['evento',      'Día de la Familia',                   '2026-08-20 09:00:00', '2026-08-20 16:00:00'],
            ['examen',      'Examen Bimestral II',                 '2026-07-10 08:00:00', '2026-07-10 12:00:00'],
            ['no_laboral',  'Navidad',                             '2026-12-25 00:00:00', '2026-12-25 23:59:00'],
            ['evento',      'Ceremonia de Clausura 2026',          '2026-12-18 09:00:00', '2026-12-18 13:00:00'],
        ];

        foreach ($eventos as [$tipo, $titulo, $inicio, $fin]) {
            if (! DB::table('eventos_calendario')->where('titulo', $titulo)->where('periodo_academico_id', $period->id)->exists()) {
                DB::table('eventos_calendario')->insert([
                    'id' => (string) Str::uuid(),
                    'periodo_academico_id' => $period->id,
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'fecha_inicio' => $inicio,
                    'fecha_fin' => $fin,
                    'descripcion' => "Evento institucional: $titulo.",
                    'seccion_id' => null,
                    'creado_por' => $this->coordinadorUserId,
                ]);
            }
        }
    }

    // =========================================================================
    // 11. COMUNICADOS
    // =========================================================================
    private function seedComunicados(): void
    {
        $comunicados = [
            [
                'titulo' => 'Inicio del Año Escolar 2026',
                'contenido' => 'Estimadas familias, les comunicamos que el año escolar 2026 inicia el lunes 9 de marzo. Se solicita puntualidad y traer útiles completos.',
                'importante' => true,
                'destinatarios' => ['roles' => ['padre', 'alumno', 'docente']],
                'fecha_publicacion' => '2026-03-05 08:00:00',
            ],
            [
                'titulo' => 'Reunión de Padres de Familia - I Bimestre',
                'contenido' => 'Se convoca a los padres de familia a la reunión de entrega de notas del primer bimestre el día sábado 30 de mayo a las 9:00 a.m.',
                'importante' => true,
                'destinatarios' => ['roles' => ['padre']],
                'fecha_publicacion' => '2026-05-20 08:00:00',
            ],
            [
                'titulo' => 'Recordatorio: Pago de Mensualidad Junio',
                'contenido' => 'Recordamos que el vencimiento del pago de mensualidad de junio es el día 15. Favor realizar el pago a tiempo para evitar recargos.',
                'importante' => false,
                'destinatarios' => ['roles' => ['padre']],
                'fecha_publicacion' => '2026-06-05 08:00:00',
            ],
            [
                'titulo' => 'Actualización del Reglamento Interno',
                'contenido' => 'Se informa a toda la comunidad educativa que el reglamento interno ha sido actualizado. Pueden descargarlo desde la plataforma.',
                'importante' => false,
                'destinatarios' => ['roles' => ['padre', 'alumno', 'docente', 'auxiliar', 'toe']],
                'fecha_publicacion' => '2026-04-01 08:00:00',
            ],
            [
                'titulo' => 'Simulacro de Sismo',
                'contenido' => 'El próximo viernes 12 de junio se realizará un simulacro de sismo a las 10:00 a.m. Solicitamos a los docentes preparar a sus estudiantes.',
                'importante' => true,
                'destinatarios' => ['roles' => ['docente', 'auxiliar', 'alumno']],
                'fecha_publicacion' => '2026-06-08 08:00:00',
            ],
        ];

        foreach ($comunicados as $c) {
            if (! DB::table('comunicados')->where('titulo', $c['titulo'])->exists()) {
                DB::table('comunicados')->insert([
                    'id' => (string) Str::uuid(),
                    'titulo' => $c['titulo'],
                    'contenido' => $c['contenido'],
                    'publicado_por' => $this->coordinadorUserId,
                    'destinatarios' => json_encode($c['destinatarios']),
                    'importante' => $c['importante'],
                    'fecha_publicacion' => $c['fecha_publicacion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // =========================================================================
    // 12. FINANZAS
    // =========================================================================
    private function seedFinanzas(PeriodoAcademico $period): void
    {
        // Configuración financiera
        if (! DB::table('configuraciones_financieras')->where('periodo_academico_id', $period->id)->exists()) {
            DB::table('configuraciones_financieras')->insert([
                'id' => (string) Str::uuid(),
                'periodo_academico_id' => $period->id,
                'dia_generacion_mensualidad' => 1,
                'dia_vencimiento_mensualidad' => 15,
                'configurado_por' => $this->coordinadorUserId,
                'vigente_desde' => '2026-03-01',
                'vigente_hasta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Conceptos de pago
        $conceptos = [
            ['Matrícula 2026',              'matricula',   400.00, null,  2026, null],
            ['Mensualidad Marzo 2026',       'mensualidad', 350.00, 20.00, 2026, 3],
            ['Mensualidad Abril 2026',       'mensualidad', 350.00, 20.00, 2026, 4],
            ['Mensualidad Mayo 2026',        'mensualidad', 350.00, 20.00, 2026, 5],
            ['Mensualidad Junio 2026',       'mensualidad', 350.00, 20.00, 2026, 6],
            ['Mensualidad Julio 2026',       'mensualidad', 350.00, 20.00, 2026, 7],
        ];

        $conceptoIds = [];
        foreach ($conceptos as [$nombre, $tipo, $monto, $descuento, $anio, $mes]) {
            $existing = DB::table('conceptos_pago')
                ->where('nombre', $nombre)
                ->where('periodo_academico_id', $period->id)
                ->first();

            if (! $existing) {
                $cid = (string) Str::uuid();
                DB::table('conceptos_pago')->insert([
                    'id' => $cid,
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'periodo_academico_id' => $period->id,
                    'periodo_anio' => $anio,
                    'periodo_mes' => $mes,
                    'monto_base' => $monto,
                    'descuento_pronto_pago' => $descuento ?? 0,
                    'fecha_limite_pronto_pago' => $mes ? "2026-$mes-05" : null,
                    'estado' => 'vigente',
                    'creado_por' => $this->coordinadorUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $conceptoIds[] = $cid;
            } else {
                $conceptoIds[] = $existing->id;
            }
        }

        // Obligaciones y pagos para los primeros 5 alumnos
        $estadosPago = ['pagado', 'pagado', 'pendiente', 'vencido', 'pagado'];
        foreach (array_slice($this->alumnos, 0, 5) as $aIdx => $alumno) {
            foreach (array_slice($conceptoIds, 0, 4) as $cIdx => $conceptoId) {
                $concepto = DB::table('conceptos_pago')->find($conceptoId);
                if (! $concepto) {
                    continue;
                }

                $estado = $estadosPago[($aIdx + $cIdx) % count($estadosPago)];
                $pagado = $estado === 'pagado';
                $oblId = (string) Str::uuid();

                $existing = DB::table('obligaciones_pago')
                    ->where('alumno_id', $alumno->id)
                    ->where('concepto_id', $conceptoId)
                    ->first();

                if ($existing) {
                    continue;
                }

                $montoOrd = $concepto->monto_base;
                $montoPP = $montoOrd - ($concepto->descuento_pronto_pago ?? 0);

                DB::table('obligaciones_pago')->insert([
                    'id' => $oblId,
                    'alumno_id' => $alumno->id,
                    'concepto_id' => $conceptoId,
                    'monto_base_snapshot' => $montoOrd,
                    'beneficio_id' => null,
                    'monto_beneficio_snapshot' => 0,
                    'descuento_pronto_pago_aplicado' => 0,
                    'monto_ordinario_snapshot' => $montoOrd,
                    'monto_pronto_pago_snapshot' => $montoPP > 0 ? $montoPP : $montoOrd,
                    'fecha_limite_pronto_pago_snapshot' => $concepto->fecha_limite_pronto_pago,
                    'monto_cobrado' => $pagado ? $montoOrd : null,
                    'fecha_vencimiento' => now()->subDays(5)->toDateString(),
                    'fecha_pago' => $pagado ? now()->subDays(rand(1, 10)) : null,
                    'estado' => $estado,
                    'registrado_por' => $this->coordinadorUserId,
                    'actualizado_finanzas_por' => null,
                    'motivo_ultima_modificacion' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($pagado) {
                    $recibo = 'REC-'.strtoupper(Str::random(8));
                    DB::table('movimientos_pago')->insert([
                        'id' => (string) Str::uuid(),
                        'obligacion_pago_id' => $oblId,
                        'tipo' => 'pago',
                        'monto' => $montoOrd,
                        'medio_pago' => 'efectivo',
                        'referencia' => null,
                        'numero_recibo' => $recibo,
                        'comprobante_ruta' => null,
                        'motivo' => null,
                        'registrado_por' => $this->coordinadorUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 13. INCIDENCIAS Y PSICOLOGÍA
    // =========================================================================
    private function seedIncidenciasYPsicologia(): void
    {
        if (empty($this->alumnos)) {
            return;
        }

        $incidencias = [
            [
                'tipo' => 'conducta',
                'severidad' => 'leve',
                'descripcion' => 'El alumno interrumpió repetidamente la clase con comportamiento disruptivo.',
                'asignado_a' => 'auxiliar',
                'estado' => 'resuelto',
            ],
            [
                'tipo' => 'academico',
                'severidad' => 'moderada',
                'descripcion' => 'Bajo rendimiento sostenido en las últimas evaluaciones de matemática.',
                'asignado_a' => 'toe',
                'estado' => 'derivado_toe',
            ],
            [
                'tipo' => 'tardanza_constante',
                'severidad' => 'leve',
                'descripcion' => 'El alumno ha llegado tarde en 8 ocasiones durante el mes.',
                'asignado_a' => 'auxiliar',
                'estado' => 'notificado_padre',
            ],
            [
                'tipo' => 'conducta',
                'severidad' => 'grave',
                'descripcion' => 'Agresión verbal hacia un compañero durante el recreo.',
                'asignado_a' => 'psicologia',
                'estado' => 'derivado_psicologia',
            ],
            [
                'tipo' => 'otro',
                'severidad' => 'leve',
                'descripcion' => 'No presentó trabajos en 3 cursos consecutivos.',
                'asignado_a' => 'toe',
                'estado' => 'abierto',
            ],
        ];

        $incidenciaIds = [];
        foreach ($incidencias as $idx => $inc) {
            $alumno = $this->alumnos[$idx % count($this->alumnos)];

            $iid = (string) Str::uuid();
            DB::table('incidencias')->insert([
                'id' => $iid,
                'alumno_id' => $alumno->id,
                'reportado_por' => $this->auxiliarUserId,
                'fecha' => now()->subDays($idx * 3 + 1),
                'tipo' => $inc['tipo'],
                'severidad' => $inc['severidad'],
                'descripcion' => $inc['descripcion'],
                'asignado_a' => $inc['asignado_a'],
                'estado' => $inc['estado'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $incidenciaIds[] = ['id' => $iid, 'alumno_id' => $alumno->id];

            // Historial
            DB::table('historial_incidencias')->insert([
                'id' => (string) Str::uuid(),
                'incidencia_id' => $iid,
                'accion' => 'Registro inicial',
                'detalle' => 'Se registró la incidencia y se asignó al área correspondiente.',
                'archivo_ruta' => null,
                'registrado_por' => $this->auxiliarUserId,
                'created_at' => now()->subDays($idx * 3 + 1),
            ]);
        }

        // Atenciones psicológicas
        foreach (array_slice($incidenciaIds, 0, 3) as $idx => $inc) {
            $alumno = $this->alumnos[$idx % count($this->alumnos)];
            DB::table('atenciones_psicologia')->insert([
                'id' => (string) Str::uuid(),
                'incidencia_id' => $inc['id'],
                'alumno_id' => $inc['alumno_id'],
                'psicologa_id' => $this->psicologiaUserId,
                'fecha_atencion' => now()->subDays($idx + 1),
                'summary' => 'Sesión de seguimiento realizada. Se observa progreso.',
                'notas_privadas' => 'Alumno muestra signos de mejora en su comportamiento. Se recomienda continuar las sesiones semanales.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // =========================================================================
    // Helper: crear/actualizar usuario demo
    // =========================================================================
    private function demoUser(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'activo' => true,
            ]
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
