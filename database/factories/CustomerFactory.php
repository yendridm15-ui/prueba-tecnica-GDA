<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Commune;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('########-#'),
            'id_com' => Commune::factory(),
            'id_reg' => fn (array $attributes): int => Commune::query()
                ->findOrFail($attributes['id_com'])
                ->id_reg,
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->optional(0.8)->address(),
            'date_reg' => now(),
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
