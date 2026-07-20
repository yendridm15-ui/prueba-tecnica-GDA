<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        Commune::query()->inRandomOrder()->take(5)->get()->each(function (Commune $commune): void {
            Customer::factory()->create([
                'id_com' => $commune->id_com,
                'id_reg' => $commune->id_reg,
            ]);
        });
    }
}
