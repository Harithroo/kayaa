<?php

namespace App\Filament\Widgets;

use App\Support\SalesReport;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Sales';

    protected ?string $description = 'Cancelled orders are not counted.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 30);
        $series = SalesReport::daily($days);

        return [
            'datasets' => [[
                'label' => 'Sales (Rs.)',
                'data' => array_map(fn ($d) => (int) ($d['revenue'] / 100), array_values($series)),
                'borderColor' => '#6E5A8C',
                'backgroundColor' => 'rgba(195, 175, 218, 0.25)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => array_map(fn ($date) => date('d M', strtotime($date)), array_keys($series)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
