<?php

namespace Database\Factories;

use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ObligacionPago> */
class ObligacionPagoFactory extends Factory
{
    protected $model = ObligacionPago::class;

    public function definition(): array
    {
        $base = fake()->randomFloat(2, 200, 1500);
        $ordinario = $base;

        return [
            'alumno_id' => Alumno::factory(),
            'concepto_id' => ConceptoPago::factory(),
            'monto_base_snapshot' => $base,
            'monto_beneficio_snapshot' => 0,
            'monto_ordinario_snapshot' => $ordinario,
            'monto_pronto_pago_snapshot' => $ordinario,
            'descuento_pronto_pago_aplicado' => 0,
            'fecha_limite_pronto_pago_snapshot' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'fecha_vencimiento' => fake()->dateTimeBetween('+2 weeks', '+1 month'),
            'estado' => 'pendiente',
            'registrado_por' => User::factory(),
        ];
    }
}
