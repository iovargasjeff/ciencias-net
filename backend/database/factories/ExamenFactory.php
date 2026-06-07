<?php

namespace Database\Factories;

use App\Models\CargaAcademica;
use App\Models\Examen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Examen> */
class ExamenFactory extends Factory
{
    protected $model = Examen::class;

    public function definition(): array
    {
        return [
            'carga_academica_id' => CargaAcademica::factory(),
            'titulo' => 'Semanal '.fake()->numberBetween(1, 4).' - '.fake()->randomElement(['I', 'II', 'III', 'IV']).' Bimestre',
            'fecha_aplicacion' => fake()->dateTimeBetween('-3 months', '+3 months')->format('Y-m-d'),
            'periodo_nombre' => fake()->randomElement(['I Bimestre', 'II Bimestre', 'III Bimestre', 'IV Bimestre']),
            'canal' => fake()->randomElement(['general', 'ciencias', 'letras']),
            'total_preguntas' => fake()->randomElement([40, 60]),
            'puntaje_maximo' => fake()->randomElement([20.00, 100.00]),
            'estado' => 'borrador',
            'publicado_por' => null,
            'publicado_en' => null,
        ];
    }

    public function publicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'publicado',
            'publicado_por' => User::factory(),
            'publicado_en' => now(),
        ]);
    }

    public function cerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrado',
        ]);
    }
}
