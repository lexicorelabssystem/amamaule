<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRow>
 */
class ImportRowFactory extends Factory
{
    protected $model = ImportRow::class;

    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            'row_number' => $this->faker->unique()->numberBetween(1, 10000),
            'raw_data' => [
                'legal_name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
            ],
            'status' => ImportRow::STATUS_PENDING,
            'errors' => null,
            'artist_id' => null,
            'user_id' => null,
        ];
    }
}
