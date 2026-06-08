<?php

namespace Database\Factories;

use App\Models\CargaAcademica;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Material> */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        $tipo = fake()->randomElement(['pdf', 'video', 'enlace', 'otro']);
        $ruta = $tipo === 'enlace'
            ? fake()->url()
            : 'storage/materiales/'.fake()->uuid().'.'.($tipo === 'pdf' ? 'pdf' : 'mp4');

        return [
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->optional()->sentence(),
            'tipo' => $tipo,
            'ruta_o_url' => $ruta,
            'carga_academica_id' => CargaAcademica::factory(),
            'subido_por' => User::factory(),
            'semana' => fake()->optional()->numberBetween(1, 40),
            'activo' => true,
        ];
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'carga_academica_id' => null,
        ]);
    }
}
