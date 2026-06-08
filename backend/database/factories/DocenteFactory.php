<?php

namespace Database\Factories;

use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Docente> */
class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dni' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'telefono' => fake()->numerify('9########'),
        ];
    }
}
