<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\SizeOption;
use App\Models\SizeScale;
use Illuminate\Database\Seeder;

/**
 * Placeholder catalogue matching the approved mockup so the storefront has something
 * to render. No images — the photo placeholder shows until real photos are uploaded.
 * Safe to delete once real products exist.
 */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $scale = SizeScale::where('name', 'Baby age')->firstOrFail();
        $sizes = $scale->options->keyBy('label');
        $colors = Color::all()->keyBy('name');
        $cat = fn (string $slug) => Category::where('slug', $slug)->firstOrFail()->id;

        $demo = [
            ['Ribbed Cotton Bodysuit', 'bodysuits', 2450, 2950, ['Butter', 'Sage', 'Blush', 'Cloud'], ['Newborn', '0–3m', '3–6m', '6–9m', '9–12m', '12–18m'], true,
                '95% OEKO-TEX certified combed cotton, 5% elastane. Ribbed knit with a little stretch so it moves with them.'],
            ['Muslin Sleep Bag 0.5 TOG', 'sleepwear', 4200, null, ['Sage', 'Oat', 'Cloud'], ['0–3m', '3–6m', '6–9m', '9–12m', '12–18m', '18–24m'], false,
                'Double-layer cotton muslin. Light enough for Colombo nights, no loose blankets in the cot.'],
            ['Bamboo Sleepsuit', 'sleepwear', 3650, null, ['Butter', 'Blush'], ['Newborn', '0–3m', '3–6m', '6–9m', '9–12m'], false,
                '70% bamboo viscose, 30% cotton. Naturally cool and very soft on new skin.'],
            ['Cotton Kurta Set', 'sets', 4950, null, ['Oat', 'Moss'], ['6–9m', '9–12m', '12–18m', '18–24m'], true,
                '100% cotton voile. Kurta and matching pants — loose, breathable, easy over the head.'],
            ['Mittens & Booties Set', 'newborn', 1650, null, ['Butter', 'Sage', 'Blush'], ['Newborn', '0–3m', '3–6m'], false,
                '100% combed cotton with soft elastic cuffs that don\'t mark.'],
            ['Reversible Bib Pair', 'accessories', 1250, null, ['Butter', 'Sage', 'Blush', 'Cloud'], ['3–6m', '6–9m', '9–12m', '12–18m'], false,
                'Cotton front, absorbent towelling back. Two in a pack.'],
            ['Pointelle Romper', 'bodysuits', 3200, null, ['Butter', 'Sage', 'Cloud'], ['Newborn', '0–3m', '3–6m', '6–9m', '9–12m'], false,
                '100% cotton pointelle knit — tiny breathing holes keep it airy.'],
            ['Waffle Knit Cardigan', 'outerwear', 3900, null, ['Oat', 'Moss'], ['3–6m', '6–9m', '9–12m', '12–18m', '18–24m'], false,
                '100% cotton waffle knit for air-conditioned rooms and hill-country mornings.'],
        ];

        $care = "Machine wash cold, gentle cycle\nTumble dry low or line dry in shade\nDo not bleach — softens further after the first wash";

        foreach ($demo as $i => [$name, $catSlug, $price, $was, $colorNames, $sizeLabels, $isNew, $fabric]) {
            $product = Product::updateOrCreate(['slug' => str($name)->slug()], [
                'name' => $name,
                'category_id' => $cat($catSlug),
                'size_scale_id' => $scale->id,
                'collection_label' => 'Kayaa Essentials',
                'description' => $fabric,
                'fabric_care' => $care,
                'status' => 'active',
                'is_new' => $isNew,
                'is_featured' => $i < 4,
            ]);

            $sku = strtoupper(substr(preg_replace('/[^A-Z]/i', '', $name), 0, 4));
            foreach ($colorNames as $ci => $colorName) {
                foreach ($sizeLabels as $si => $label) {
                    /** @var SizeOption $size */
                    $size = $sizes[$label];
                    // A little variety so the mockup states (sold out, low stock) show up.
                    $stock = ($i === 0 && $label === '6–9m') ? 0 : (($i === 0 && $label === '0–3m') ? 3 : 12);

                    $product->variants()->updateOrCreate(
                        ['size_option_id' => $size->id, 'color_id' => $colors[$colorName]->id],
                        [
                            'sku' => sprintf('%s-%02d-%02d', $sku, $ci + 1, $si + 1),
                            'price' => $price * 100,
                            'compare_at_price' => $was ? $was * 100 : null,
                            'stock' => $stock,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
