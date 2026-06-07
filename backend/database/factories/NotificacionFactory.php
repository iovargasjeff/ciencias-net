<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Notificacion> */
class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'tipo'      => fake()->randomElement(['nota_publicada', 'asistencia', 'pago', 'comunicado']),
            'titulo'    => fake()->sentence(4),
            'contenido' => fake()->sentence(10),
            'datos'     => ['id' => fake()->uuid()],
            'canal'     => fake()->randomElement(['panel', 'correo']),
            'estado'    => 'pendiente',
            'enviada_en' => null,
            'leida_en'   => null,
        ];
    }

    public function enviada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado'    => 'enviada',
            'enviada_en' => now(),
        ]);
    }

    public function leida(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado'    => 'leida',
            'enviada_en' => now()->subMinutes(10),
            'leida_en'   => now(),
        ]);
    }
}
