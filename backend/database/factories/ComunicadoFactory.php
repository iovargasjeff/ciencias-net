<?php

namespace Database\Factories;

use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comunicado> */
class ComunicadoFactory extends Factory
{
    protected $model = Comunicado::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(5),
            'contenido' => fake()->paragraphs(2, true),
            'publicado_por' => User::factory(),
            'destinatarios' => ['roles' => ['padre', 'docente']],
            'importante' => false,
            'fecha_publicacion' => now(),
        ];
    }

    public function importante(): static
    {
        return $this->state(fn (array $attributes) => [
            'importante' => true,
        ]);
    }
}
