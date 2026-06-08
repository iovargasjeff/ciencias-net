<?php

namespace Database\Factories;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Modules\Academico\Infrastructure\Models\Examen;
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
            'assessment_type' => fake()->randomElement(['exam', 'practice', 'project', 'participation', 'other']),
            'channel' => fake()->randomElement(['general', 'sciences', 'humanities']),
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
