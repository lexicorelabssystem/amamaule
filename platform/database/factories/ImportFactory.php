<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => $this->faker->word().'.csv',
            'stored_filename' => $this->faker->uuid().'.csv',
            'status' => Import::STATUS_PENDING,
            'total_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'headers' => null,
            'validation_errors' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
