<?php

namespace Database\Factories;

use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BeneficioAlumno> */
class BeneficioAlumnoFactory extends Factory
{
    protected $model = BeneficioAlumno::class;

    public function definition(): array
    {
        return [
            'alumno_id' => Alumno::factory(),
            'tipo' => fake()->randomElement(['beca', 'descuento']),
            'modalidad' => fake()->randomElement(['porcentaje', 'monto_fijo']),
            'valor' => fake()->randomFloat(2, 10, 500),
            'aplica_mensualidad' => true,
            'aplica_matricula' => false,
            'aplica_cuota_ingreso' => false,
            'acumulable_pronto_pago' => true,
            'vigente_desde' => fake()->dateTimeBetween('-1 year', 'now'),
            'motivo' => fake()->sentence(),
            'activo' => true,
            'registrado_por' => User::factory(),
        ];
    }
}
