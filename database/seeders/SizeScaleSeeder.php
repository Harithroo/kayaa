<?php

namespace Database\Seeders;

use App\Models\SizeScale;
use Illuminate\Database\Seeder;

class SizeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scale = SizeScale::firstOrCreate(['name' => 'Baby age'], ['type' => 'age']);

        $rows = [
            //  label      pos  minH  maxH  minW  maxW
            ['Newborn',    10,  null, 50,   null, 3.5],
            ['0–3m',       20,  50,   58,   3.5,  5.5],
            ['3–6m',       30,  58,   66,   5.5,  7.5],
            ['6–9m',       40,  66,   72,   7.5,  9.0],
            ['9–12m',      50,  72,   78,   9.0,  10.5],
            ['12–18m',     60,  78,   86,   10.5, 12.0],
            ['18–24m',     70,  86,   92,   12.0, 13.5],
            ['2Y',         80,  92,   98,   13.5, 15.0],
            ['3Y',         90,  98,   104,  15.0, 17.0],
        ];

        foreach ($rows as [$label, $pos, $minH, $maxH, $minW, $maxW]) {
            $scale->options()->updateOrCreate(['label' => $label], [
                'position' => $pos,
                'min_height_cm' => $minH, 'max_height_cm' => $maxH,
                'min_weight_kg' => $minW, 'max_weight_kg' => $maxW,
            ]);
        }

        SizeScale::firstOrCreate(['name' => 'One size'], ['type' => 'one_size'])
            ->options()->updateOrCreate(['label' => 'One size'], ['position' => 10]);
    }
}
