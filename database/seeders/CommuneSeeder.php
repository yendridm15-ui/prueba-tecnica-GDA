<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CommuneSeeder extends Seeder
{
    public function run(): void
    {
        $communesByRegion = [
            'Distrito Capital' => ['Caracas', 'El Valle', 'La Vega', 'Catia'],
            'Miranda' => ['Chacao', 'Baruta', 'Petare', 'Los Teques'],
            'Zulia' => ['Maracaibo', 'Cabimas', 'Ciudad Ojeda'],
            'Carabobo' => ['Valencia', 'Puerto Cabello', 'Guacara'],
        ];

        foreach ($communesByRegion as $regionDescription => $communes) {
            $region = Region::query()->where('description', $regionDescription)->first();

            if ($region === null) {
                continue;
            }

            foreach ($communes as $communeDescription) {
                Commune::query()->firstOrCreate([
                    'id_reg' => $region->id_reg,
                    'description' => $communeDescription,
                ]);
            }
        }
    }
}
