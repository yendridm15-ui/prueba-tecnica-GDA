<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            'Distrito Capital',
            'Miranda',
            'Zulia',
            'Carabobo',
        ];

        foreach ($regions as $description) {
            Region::query()->firstOrCreate(['description' => $description]);
        }
    }
}
