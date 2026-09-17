<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SizeScaleSeeder::class,
            CatalogStructureSeeder::class,
            BannerSeeder::class,
            DemoProductSeeder::class, // placeholder products; remove once real products are in
        ]);
    }
}
