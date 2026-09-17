<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::firstOrCreate(['placement' => 'topbar', 'title' => 'Island-wide delivery in 2–4 days · Free over Rs. 7,500'], ['is_active' => true]);
        Banner::firstOrCreate(['placement' => 'home_promo', 'title' => 'Up to 30% off sleepwear'], [
            'eyebrow' => 'Mid-season',
            'body' => 'Sleep bags, sleepsuits and vests. While stocks last.',
            'cta_label' => 'Shop the sale',
            'cta_url' => '/shop?sale=1',
            'style' => 'ink',
            'is_active' => true,
        ]);
    }
}
