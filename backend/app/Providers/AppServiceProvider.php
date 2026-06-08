<?php

namespace App\Providers;

use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Presentation\Policies\ExamenPolicy;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Presentation\Policies\AlumnoPolicy;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Modules\Usuarios\Presentation\Policies\UserPolicy;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Presentation\Policies\PeriodoAcademicoPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $basename = class_basename($modelName);
            return 'Database\\Factories\\' . $basename . 'Factory';
        });

        Factory::guessModelNamesUsing(function (Factory $factory) {
            $modelName = str_replace('Factory', '', class_basename($factory));
            $map = [
                'User' => 'Usuarios', 'Alumno' => 'Usuarios', 'Docente' => 'Usuarios', 'Padre' => 'Usuarios', 'Administrativo' => 'Usuarios', 'ConsentimientoBiometrico' => 'Usuarios', 'PerfilFacial' => 'Usuarios',
                'CargaAcademica' => 'Academico', 'Curso' => 'Academico', 'Grado' => 'Academico', 'Matricula' => 'Academico', 'PeriodoAcademico' => 'Academico', 'ReporteAcademico' => 'Academico', 'Seccion' => 'Academico', 'Nota' => 'Academico', 'EventoCalendario' => 'Academico', 'Examen' => 'Academico',
                'Material' => 'Materiales', 'Horario' => 'Horarios', 'Comunicado' => 'Comunicados', 'ComunicadoLectura' => 'Comunicados', 'Notificacion' => 'Notificaciones',
                'BeneficioAlumno' => 'Finanzas', 'ConceptoPago' => 'Finanzas', 'ConfiguracionFinanciera' => 'Finanzas', 'MovimientoPago' => 'Finanzas', 'ObligacionPago' => 'Finanzas',
            ];
            if (isset($map[$modelName])) {
                $module = $map[$modelName];
                $domainClass = "App\\Modules\\{$module}\\Domain\\Models\\{$modelName}";
                if (class_exists($domainClass)) return $domainClass;
                return "App\\Modules\\{$module}\\Infrastructure\\Models\\{$modelName}";
            }
            return "App\\Models\\{$modelName}";
        });
        Gate::policy(Examen::class, ExamenPolicy::class);
        Gate::policy(Alumno::class, AlumnoPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PeriodoAcademico::class, PeriodoAcademicoPolicy::class);

        RateLimiter::for('human-login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('password-recovery', fn (Request $request) => [
            Limit::perMinute(3)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('station-activation', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
        ]);

        RateLimiter::for('station-capture', fn (Request $request) => [
            Limit::perMinute(60)->by($request->ip()),
        ]);
    }
}
