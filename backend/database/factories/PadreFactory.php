<?php

namespace Database\Factories;

use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Padre> */
class PadreFactory extends Factory
{
    protected $model = Padre::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dni' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'celular' => fake()->numerify('9########'),
            'correo_notificaciones' => fake()->unique()->safeEmail(),
        ];
    }
}
