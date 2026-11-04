<?php

namespace Tests\Unit\Seo;

use App\Support\Seo\MetaText;
use PHPUnit\Framework\TestCase;

/** The one text pipeline every title and description goes through. */
class MetaTextTest extends TestCase
{
    public function test_it_strips_tags_entities_and_collapsed_whitespace(): void
    {
        $this->assertSame(
            'Soft organic cotton & bamboo',
            MetaText::flatten("<p>Soft <b>organic</b>\n  cotton &amp; bamboo</p>\t")
        );
    }

    public function test_it_does_not_let_a_decoded_entity_become_a_tag(): void
    {
        // Tags are stripped before entities are decoded, so escaped markup in
        // admin copy survives as readable text instead of being eaten.
        $this->assertSame('bold text', MetaText::flatten('&lt;b&gt;bold&lt;/b&gt; text'));
    }

    public function test_empty_and_tag_only_input_become_null_so_callers_can_fall_through(): void
    {
        $this->assertNull(MetaText::flatten(null));
        $this->assertNull(MetaText::flatten(''));
        $this->assertNull(MetaText::flatten('   '));
        $this->assertNull(MetaText::flatten('<p></p>'));
    }

    public function test_it_cuts_on_a_word_boundary_without_a_mid_word_ellipsis(): void
    {
        $cut = MetaText::clamp('Organic cotton bodysuit for newborns and older babies', 30);

        $this->assertLessThanOrEqual(30, mb_strlen($cut));
        $this->assertStringEndsNotWith('-', $cut);
        // The cut must land between words, never inside one.
        $this->assertSame($cut, rtrim($cut));
        $this->assertStringContainsString('Organic cotton', $cut);
        $this->assertStringNotContainsString('…', $cut);
    }

    public function test_a_string_within_the_limit_is_returned_untouched(): void
    {
        $this->assertSame('Short enough', MetaText::clamp('Short enough', 40));
    }

    public function test_a_single_over_long_word_is_hard_cut_rather_than_emptied(): void
    {
        // An over-long title beats no title at all.
        $this->assertSame('Aaaaa', MetaText::clamp('Aaaaaaaaaaaaaaaa', 5));
    }

    public function test_it_trims_punctuation_left_dangling_by_the_cut(): void
    {
        $this->assertSame('Soft cotton', MetaText::clamp('Soft cotton, breathable and light', 13));
    }

    public function test_it_is_multibyte_safe(): void
    {
        $cut = MetaText::clamp('Bodysuit 0–3m and 3–6m sizes available now', 20);

        $this->assertLessThanOrEqual(20, mb_strlen($cut));
        $this->assertSame($cut, mb_convert_encoding($cut, 'UTF-8', 'UTF-8'));
    }

    public function test_summarise_flattens_then_clamps(): void
    {
        $this->assertSame('Soft cotton', MetaText::summarise('<p>Soft   cotton, breathable</p>', 13));
        $this->assertNull(MetaText::summarise(null));
    }
}
