<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'United Kingdom', 'code' => '44', 'status' => true],
            ['name' => 'Netherlands', 'code' => '31', 'status' => true],
            ['name' => 'France', 'code' => '33', 'status' => true],
            ['name' => 'Spain', 'code' => '34', 'status' => true],
            ['name' => 'Poland', 'code' => '48', 'status' => true],
            ['name' => 'Colombia', 'code' => '57', 'status' => true],
        ];

        $codes = array_column($countries, 'code');

        // Delete countries not in our whitelist
        Country::whereNotIn('code', $codes)->delete();

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
