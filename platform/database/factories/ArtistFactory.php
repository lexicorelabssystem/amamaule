<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    public function definition(): array
    {
        $publicName = $this->faker->name();

        return [
            'legal_name' => $this->faker->name(),
            'public_name' => $publicName,
            'artistic_name' => $this->faker->optional()->words(2, true),
            'slug' => Str::slug($publicName . ' ' . Str::random(6)),
            'email_contact' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'document_number' => $this->faker->unique()->numerify('##.###.###-#'),
            'region' => $this->faker->state(),
            'commune' => $this->faker->city(),
            'address' => $this->faker->optional()->address(),
            'bio_short' => $this->faker->optional()->text(200),
            'bio_long' => $this->faker->optional()->paragraphs(3, true),
            'social_networks' => null,
            'status' => Artist::STATUS_DRAFT,
        ];
    }
}
