<?php

namespace Database\Factories;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PeriodoAcademico> */
class PeriodoAcademicoFactory extends Factory
{
    protected $model = PeriodoAcademico::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2025, 2035);

        return [
            'nombre' => "Año lectivo {$year}",
            'tipo' => 'colegio',
            'fecha_inicio' => "{$year}-03-01",
            'fecha_fin' => "{$year}-12-20",
            'estado' => 'borrador',
            'creado_por' => User::factory(),
        ];
    }
}
