<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Writes public/sitemap.xml.
 *
 * Deliberately hand-rolled rather than a package: the whole job is sixty lines
 * of XML over two tables, and a dependency here would need upgrading for the
 * rest of the site's life.
 *
 * Only pages that should actually be indexed are listed. A sitemap that offers
 * up drafts, empty categories or records flagged noindex spends crawl budget
 * teaching Google about pages it has been told to ignore.
 */
class GenerateSitemap extends Command
{
    protected $signature = 'kayaa:sitemap';

    protected $description = 'Write public/sitemap.xml from the live catalogue';

    /** Split into a sitemap index above this many URLs. */
    private const MAX_URLS_PER_FILE = 1000;

    public function handle(): int
    {
        $urls = $this->urls();

        if ($urls->isEmpty()) {
            $this->error('Nothing to write: no active products or categories.');

            return self::FAILURE;
        }

        $chunks = $urls->chunk(self::MAX_URLS_PER_FILE)->values();

        // One file while the catalogue is small, which it will be for a long
        // time. The split is written now so it is not a crisis later.
        if ($chunks->count() === 1) {
            $this->write('sitemap.xml', $this->urlSet($chunks->first()));
            $this->cleanUpChunks();
            $this->info('Wrote public/sitemap.xml with '.$urls->count().' URLs.');

            return self::SUCCESS;
        }

        $files = [];

        foreach ($chunks as $i => $chunk) {
            $name = 'sitemap-'.($i + 1).'.xml';
            $this->write($name, $this->urlSet($chunk));
            $files[] = url($name);
        }

        $this->write('sitemap.xml', $this->sitemapIndex($files));
        $this->info('Wrote public/sitemap.xml as an index over '.count($files).' files ('.$urls->count().' URLs).');

        return self::SUCCESS;
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string, priority: string}> */
    private function urls(): Collection
    {
        $urls = collect();

        // Home first: priority 1.0, and the only page given it.
        $urls->push(['loc' => route('home'), 'lastmod' => null, 'priority' => '1.0']);

        foreach (['shop.index', 'categories.index', 'pages.size-guide', 'pages.contact'] as $route) {
            $urls->push(['loc' => route($route), 'lastmod' => null, 'priority' => '0.6']);
        }

        foreach (['about', 'delivery', 'returns', 'privacy', 'terms'] as $page) {
            $urls->push(['loc' => route('pages.show', $page), 'lastmod' => null, 'priority' => '0.3']);
        }

        // Categories holding at least one active product. An empty category is
        // a dead end for a crawler and a bad result for a shopper.
        $categories = Category::query()
            ->where('is_active', true)
            ->where('noindex', false)
            ->whereHas('products', fn ($q) => $q->where('status', 'active'))
            ->with('department')
            ->get();

        foreach ($categories as $category) {
            $urls->push([
                'loc' => $category->url(),
                'lastmod' => $this->stamp($category->updated_at),
                'priority' => '0.8',
            ]);
        }

        $products = Product::query()
            ->where('status', 'active')
            ->where('noindex', false)
            ->get(['id', 'slug', 'updated_at']);

        foreach ($products as $product) {
            $urls->push([
                'loc' => route('products.show', $product->slug),
                'lastmod' => $this->stamp($product->updated_at),
                'priority' => '0.7',
            ]);
        }

        return $urls->unique('loc')->values();
    }

    /** @param  Collection<int, array{loc: string, lastmod: ?string, priority: string}>  $urls */
    private function urlSet(Collection $urls): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($url['loc']).'</loc>';

            if ($url['lastmod']) {
                $xml[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $xml[] = '    <priority>'.$url['priority'].'</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml)."\n";
    }

    /** @param  array<int, string>  $files */
    private function sitemapIndex(array $files): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($files as $file) {
            $xml[] = '  <sitemap><loc>'.e($file).'</loc></sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode("\n", $xml)."\n";
    }

    private function write(string $name, string $contents): void
    {
        file_put_contents(public_path($name), $contents);
    }

    /**
     * Remove chunk files from a previous, larger run so a shrinking catalogue
     * does not leave stale URLs being served.
     */
    private function cleanUpChunks(): void
    {
        foreach (glob(public_path('sitemap-*.xml')) ?: [] as $stale) {
            @unlink($stale);
        }
    }

    /** W3C datetime, which is what <lastmod> expects. */
    private function stamp(?CarbonInterface $at): ?string
    {
        return $at?->toAtomString();
    }
}
