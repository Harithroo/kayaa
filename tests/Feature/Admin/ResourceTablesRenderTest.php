<?php

namespace Tests\Feature\Admin;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * Every resource list page must render.
 *
 * Only some resources had a list test, and a broken table shows up as a 500 on
 * exactly these pages — a missing PHP extension took all of them out in
 * production while the dashboard kept working, and the suite stayed green
 * because most of them were never rendered. This walks the whole directory, so
 * a new resource is covered the day it is added rather than when someone
 * remembers to write a test for it.
 *
 * It renders twice: once with no rows, which is the state a fresh install and
 * most filters land in, and once with shop data, so the column closures
 * actually run against a record.
 */
class ResourceTablesRenderTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    /**
     * @return array<string, array{class-string<ListRecords>}>
     */
    public static function listPageProvider(): array
    {
        // A data provider runs before the application is booted, so the path is
        // resolved from this file rather than through app_path().
        $finder = Finder::create()
            ->files()
            ->in(dirname(__DIR__, 3).'/app/Filament/Resources')
            ->path('Pages')
            ->name('List*.php');

        $pages = [];

        foreach ($finder as $file) {
            $class = 'App\\Filament\\Resources\\'.Str::of($file->getRelativePathname())
                ->replace(['/', '\\'], '\\')
                ->replace('.php', '')
                ->toString();

            if (! class_exists($class) || ! is_subclass_of($class, ListRecords::class)) {
                continue;
            }

            $pages[class_basename($class)] = [$class];
        }

        // A silent zero here would make every assertion below vacuous.
        self::assertNotEmpty($pages, 'No Filament list pages were discovered.');

        return $pages;
    }

    /**
     * @param  class-string<ListRecords>  $page
     */
    #[DataProvider('listPageProvider')]
    public function test_the_list_page_renders_when_empty(string $page): void
    {
        $this->actingAsAdmin();

        Livewire::test($page)->assertOk();
    }

    /**
     * @param  class-string<ListRecords>  $page
     */
    #[DataProvider('listPageProvider')]
    public function test_the_list_page_renders_with_records(string $page): void
    {
        $this->actingAsAdmin();
        $this->customer();
        // An order drags in a product, a variant and its category, so every
        // resource's table has at least one row to format.
        $this->order([], [$this->variant()]);

        Livewire::test($page)->assertOk();
    }
}
