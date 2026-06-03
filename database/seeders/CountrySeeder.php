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
            ['name' => 'United States', 'code' => '1', 'status' => true],
            ['name' => 'United Kingdom', 'code' => '44', 'status' => true],
            ['name' => 'Germany', 'code' => '49', 'status' => true],
            ['name' => 'Spain', 'code' => '34', 'status' => true],
            ['name' => 'India', 'code' => '91', 'status' => true],
            ['name' => 'Brazil', 'code' => '55', 'status' => true],
            ['name' => 'Netherlands', 'code' => '31', 'status' => true],
            ['name' => 'Croatia', 'code' => '385', 'status' => true],
            ['name' => 'Morocco', 'code' => '212', 'status' => true],
            ['name' => 'Bulgaria', 'code' => '359', 'status' => true],
            ['name' => 'Mexico', 'code' => '52', 'status' => true],
            ['name' => 'Nigeria', 'code' => '234', 'status' => true],
            ['name' => 'Ukraine', 'code' => '380', 'status' => true],
            ['name' => 'Israel', 'code' => '972', 'status' => true],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
