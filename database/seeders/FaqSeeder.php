<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/** Starter FAQs. Everything here is editable in the admin panel under FAQs. */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['product', 'How do I choose the right size?', 'Our sizes follow baby age bands, not height alone. If your little one is between bands, size up — most babies grow through a band in about six weeks. The size guide on this page lists height and weight for each band.'],
            ['product', 'How should I wash it?', 'Machine wash cold on a gentle cycle with like colours, and tumble dry low or line dry in the shade. Skip fabric softener — it coats cotton and makes it less breathable.'],
            ['product', 'Will it shrink?', 'All our cotton is pre-shrunk, so expect no more than minimal settling after the first wash. Washing cold keeps it that way.'],
            ['product', 'Can I exchange it for another size?', 'Yes. Any unworn item with tags on can be exchanged within 14 days, and we cover return delivery on size swaps.'],
            ['contact', 'How long does delivery take?', "Colombo is 1–2 working days and the rest of the island 2–4. You will get the courier's tracking as soon as the parcel leaves us."],
            ['contact', 'Do you offer cash on delivery?', 'Yes, island-wide. You can also pay online by card at checkout.'],
            ['contact', 'How do I track my order?', 'Use the order number from your confirmation on the Track your order page, or sign in to your account to see all of your orders in one place.'],
            ['contact', 'Can I change or cancel my order?', 'Message us on WhatsApp as soon as you can. If the parcel has not been handed to the courier yet, we can change or cancel it.'],
            ['contact', 'How do returns and exchanges work?', 'Unworn items with tags on can be returned or exchanged within 14 days. Message us first and we will arrange the pickup.'],
        ];

        foreach ($faqs as $i => [$placement, $question, $answer]) {
            Faq::firstOrCreate(
                ['placement' => $placement, 'question' => $question],
                ['answer' => $answer, 'position' => $i, 'is_active' => true],
            );
        }
    }
}
