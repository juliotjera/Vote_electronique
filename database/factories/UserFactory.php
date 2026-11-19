<?php

namespace Database\Factories;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricule' => (string) fake()->unique()->numberBetween(20230001, 20269999),
            'nom' => mb_strtoupper(fake()->lastName()),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'electeur',
            'classe_id' => Classe::factory(),
            'actif' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin', 'matricule' => null, 'classe_id' => null]);
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['actif' => false]);
    }
}
