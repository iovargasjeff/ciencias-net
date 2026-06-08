<?php

namespace Database\Factories;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConceptoPago> */
class ConceptoPagoFactory extends Factory
{
    protected $model = ConceptoPago::class;

    public function definition(): array
    {
        $academicPeriod = PeriodoAcademico::factory()->create();
        $anio = (int) date('Y');

        return [
            'nombre' => fake()->randomElement(['Matrícula', 'Mensualidad', 'Cuota de Ingreso', 'Seguro']),
            'codigo' => fake()->unique()->regexify('[A-Z]{3}-\d{4}'),
            'tipo' => fake()->randomElement(['mensualidad', 'matricula', 'cuota_ingreso', 'otro']),
            'periodo_academico_id' => $academicPeriod->id,
            'periodo_anio' => $anio,
            'periodo_mes' => fake()->numberBetween(1, 12),
            'monto_base' => fake()->randomFloat(2, 100, 2000),
            'descuento_pronto_pago' => 0,
            'fecha_limite_pronto_pago' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'estado' => 'vigente',
            'creado_por' => User::factory(),
        ];
    }
}
