<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Nota> */
class NotaFactory extends Factory
{
    protected $model = Nota::class;

    public function definition(): array
    {
        return [
            'examen_id'      => Examen::factory(),
            'matricula_id'   => Matricula::factory(),
            'puntaje'        => fake()->randomFloat(2, 0, 20),
            'estado'         => 'registrada',
            'observacion'    => null,
            'puesto_ranking' => null,
            'registrado_por' => User::factory(),
        ];
    }

    public function ausente(): static
    {
        return $this->state(fn (array $attributes) => [
            'puntaje' => null,
            'estado'  => 'ausente',
        ]);
    }

    public function exonerado(): static
    {
        return $this->state(fn (array $attributes) => [
            'puntaje' => null,
            'estado'  => 'exonerado',
        ]);
    }

    public function conRanking(int $puesto): static
    {
        return $this->state(fn (array $attributes) => [
            'puesto_ranking' => $puesto,
        ]);
    }
}
