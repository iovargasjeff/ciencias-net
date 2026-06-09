<?php

namespace Database\Factories;

use App\Modules\Academico\Infrastructure\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Curso> */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('CUR-###??'),
            'nombre' => fake()->words(2, true),
            'area' => fake()->randomElement(['Ciencias', 'Letras', 'General']),
            'activo' => true,
        ];
    }
}
