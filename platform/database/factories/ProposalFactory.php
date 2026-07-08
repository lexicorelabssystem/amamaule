<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(), 'title' => fake()->sentence(4),
            'description' => fake()->paragraph(), 'budget' => fake()->randomFloat(2, 0, 1000000),
            'status' => Proposal::STATUS_DRAFT,
        ];
    }
}
