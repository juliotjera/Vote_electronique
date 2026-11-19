<?php

namespace Database\Factories;

use App\Models\Candidature;
use App\Models\Scrutin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidature>
 */
class CandidatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scrutin_id' => Scrutin::factory(),
            'user_id' => User::factory(),
            'slogan' => fake()->sentence(5),
            'programme' => fake()->paragraph(),
            'statut' => 'validee',
        ];
    }
}
