<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'mediable_type' => Activity::class,
            'mediable_id' => Activity::factory(),
            'collection_name' => 'gallery',
            'file_name' => $this->faker->uuid().'.jpg',
            'file_path' => 'media/gallery/2026/07/'.$this->faker->uuid().'.jpg',
            'thumbnail_path' => 'media/gallery/2026/07/'.$this->faker->uuid().'_thumb.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10000, 5000000),
            'order' => 1,
            'is_cover' => false,
        ];
    }
}
