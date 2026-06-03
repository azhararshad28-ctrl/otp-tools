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
            ['name' => 'Greece', 'code' => '30', 'status' => true],
            ['name' => 'Netherlands', 'code' => '31', 'status' => true],
            ['name' => 'Belgium', 'code' => '32', 'status' => true],
            ['name' => 'France', 'code' => '33', 'status' => true],
            ['name' => 'Spain', 'code' => '34', 'status' => true],
            ['name' => 'Hungary', 'code' => '36', 'status' => true],
            ['name' => 'Switzerland', 'code' => '41', 'status' => true],
            ['name' => 'Austria', 'code' => '43', 'status' => true],
            ['name' => 'Sweden', 'code' => '46', 'status' => true],
            ['name' => 'Norway', 'code' => '47', 'status' => true],
            ['name' => 'Poland', 'code' => '48', 'status' => true],
            ['name' => 'Argentina', 'code' => '54', 'status' => true],
            ['name' => 'Colombia', 'code' => '57', 'status' => true],
            ['name' => 'Luxembourg', 'code' => '352', 'status' => true],
            ['name' => 'Ireland', 'code' => '353', 'status' => true],
            ['name' => 'Bulgaria', 'code' => '359', 'status' => true],
            ['name' => 'Latvia', 'code' => '371', 'status' => true],
            ['name' => 'Estonia', 'code' => '372', 'status' => true],
            ['name' => 'Czech Republic', 'code' => '420', 'status' => true],
            ['name' => 'Slovakia', 'code' => '421', 'status' => true],
            ['name' => 'Georgia', 'code' => '995', 'status' => true],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
