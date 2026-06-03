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
            ['name' => 'United States', 'code' => 'US', 'status' => true],
            ['name' => 'United Kingdom', 'code' => 'GB', 'status' => true],
            ['name' => 'Canada', 'code' => 'CA', 'status' => true],
            ['name' => 'Australia', 'code' => 'AU', 'status' => true],
            ['name' => 'India', 'code' => 'IN', 'status' => true],
            ['name' => 'Germany', 'code' => 'DE', 'status' => true],
            ['name' => 'France', 'code' => 'FR', 'status' => true],
            ['name' => 'Spain', 'code' => 'ES', 'status' => true],
            ['name' => 'Italy', 'code' => 'IT', 'status' => true],
            ['name' => 'Brazil', 'code' => 'BR', 'status' => true],
            ['name' => 'Russia', 'code' => 'RU', 'status' => true],
            ['name' => 'Pakistan', 'code' => 'PK', 'status' => true],
            ['name' => 'Indonesia', 'code' => 'ID', 'status' => true],
            ['name' => 'Malaysia', 'code' => 'MY', 'status' => true],
            ['name' => 'Turkey', 'code' => 'TR', 'status' => true],
            ['name' => 'Egypt', 'code' => 'EG', 'status' => true],
            ['name' => 'South Africa', 'code' => 'ZA', 'status' => true],
            ['name' => 'Japan', 'code' => 'JP', 'status' => true],
            ['name' => 'South Korea', 'code' => 'KR', 'status' => true],
            ['name' => 'Vietnam', 'code' => 'VN', 'status' => true],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
