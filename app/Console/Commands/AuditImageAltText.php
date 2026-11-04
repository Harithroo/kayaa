<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;

/**
 * Lists product photos with no alt text.
 *
 * Alt text is required in the admin from now on, but rows saved before that are
 * still blank, and a missing alt is invisible on the page — nobody notices until
 * a screen reader hits it. This makes the backlog something that can be worked
 * through rather than discovered.
 */
class AuditImageAltText extends Command
{
    protected $signature = 'kayaa:audit-alt-text {--fail : Exit non-zero when any photo is missing alt text}';

    protected $description = 'List product photos with missing alt text';

    public function handle(): int
    {
        $missing = ProductImage::query()
            ->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''))
            ->with('product:id,name,slug')
            ->get();

        if ($missing->isEmpty()) {
            $this->info('Every product photo has alt text.');

            return self::SUCCESS;
        }

        $this->warn($missing->count().' photo(s) have no alt text:');

        $this->table(
            ['Product', 'Photo', 'Edit'],
            $missing->map(fn (ProductImage $image) => [
                $image->product?->name ?? '(deleted product)',
                $image->path,
                $image->product ? url('/admin/products/'.$image->product->id.'/edit') : '—',
            ])->all()
        );

        return $this->option('fail') ? self::FAILURE : self::SUCCESS;
    }
}
