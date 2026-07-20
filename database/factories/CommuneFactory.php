<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Commune;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commune>
 */
class CommuneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_reg' => Region::factory(),
            'description' => fake()->unique()->city(),
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }

    public function trash(): static
    {
        return $this->state(['status' => Status::Trash]);
    }
}
