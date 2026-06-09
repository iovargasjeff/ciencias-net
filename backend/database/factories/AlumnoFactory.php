<?php

namespace Database\Factories;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alumno> */
class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
        ];
    }
}
