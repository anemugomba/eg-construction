<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Main Yard',
                'location' => 'Harare',
                'is_active' => true,
            ],
            [
                'name' => 'Site A - Kariba',
                'location' => 'Kariba',
                'is_active' => true,
            ],
            [
                'name' => 'Site B - Hwange',
                'location' => 'Hwange',
                'is_active' => true,
            ],
            [
                'name' => 'Site C - Bulawayo',
                'location' => 'Bulawayo',
                'is_active' => true,
            ],
            [
                'name' => 'Site D - Mutare',
                'location' => 'Mutare',
                'is_active' => true,
            ],
            [
                'name' => 'Site E - Masvingo',
                'location' => 'Masvingo',
                'is_active' => true,
            ],
        ];

        foreach ($sites as $site) {
            Site::firstOrCreate(
                ['name' => $site['name']],
                $site
            );
        }
    }
}
