<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'artist_id' => Artist::factory(),
            'territory_id' => null,
            'title' => $title,
            'slug' => Str::slug($title.' '.Str::random(6)),
            'short_description' => $this->faker->text(200),
            'description' => $this->faker->paragraphs(3, true),
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'end_date' => null,
            'location' => $this->faker->city(),
            'category' => $this->faker->word(),
            'capacity' => $this->faker->numberBetween(10, 500),
            'is_free' => true,
            'price' => null,
            'status' => Activity::STATUS_DRAFT,
            'cover_media_id' => null,
        ];
    }
}
