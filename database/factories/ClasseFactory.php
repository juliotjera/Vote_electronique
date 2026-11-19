<?php

namespace Database\Factories;

use App\Models\Classe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classe>
 */
class ClasseFactory extends Factory
{
    public function definition(): array
    {
        return ['nom' => fake()->unique()->bothify('L# ??')];
    }
}
