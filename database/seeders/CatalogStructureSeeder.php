<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CatalogStructureSeeder extends Seeder
{
    public function run(): void
    {
        $baby = Department::updateOrCreate(['slug' => 'baby'], ['name' => 'Baby', 'position' => 10]);

        $cats = ['Newborn', 'Bodysuits', 'Sleepwear', 'Sets', 'Outerwear', 'Napkins', 'Accessories'];
        foreach ($cats as $i => $name) {
            Category::updateOrCreate(
                ['department_id' => $baby->id, 'slug' => str($name)->slug()],
                ['name' => $name, 'position' => ($i + 1) * 10],
            );
        }

        $colors = [
            ['Butter', '#FBEBD2'], ['Sage', '#E4EDE6'], ['Blush', '#F6E4E1'],
            ['Cloud', '#E7E9F2'], ['Moss', '#E9EFDC'], ['Oat', '#F1EAE0'],
            ['White', '#FFFFFF'],
        ];
        foreach ($colors as [$name, $hex]) {
            Color::updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'hex' => $hex]);
        }
    }
}
