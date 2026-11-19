<?php

namespace Database\Factories;

use App\Models\Scrutin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scrutin>
 */
class ScrutinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre' => 'Élection des délégués '.fake()->bothify('L#'),
            'type' => 'uninominal',
            'nombre_choix' => 1,
            'nombre_elus' => 1,
            'vote_blanc' => true,
            'ouverture_at' => now()->addDay(),
            'cloture_at' => now()->addDays(3),
        ];
    }

    // Vote en cours
    public function ouvert(): static
    {
        return $this->state(fn () => ['ouverture_at' => now()->subHour(), 'cloture_at' => now()->addDays(2)])
            ->afterMaking(fn (Scrutin $s) => $s->programme_at = now()->subDay());
    }

    public function programme(): static
    {
        return $this->afterMaking(fn (Scrutin $s) => $s->programme_at = now()->subHour());
    }

    public function clos(): static
    {
        return $this->state(fn () => ['ouverture_at' => now()->subDays(3), 'cloture_at' => now()->subHour()])
            ->afterMaking(fn (Scrutin $s) => $s->programme_at = now()->subDays(4));
    }

    public function plurinominal(int $choix): static
    {
        return $this->state(fn () => ['type' => 'plurinominal', 'nombre_choix' => $choix, 'nombre_elus' => $choix]);
    }
}
