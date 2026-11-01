<?php

namespace App\Support;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Numbers behind the admin dashboard. A cancelled order is not a sale, so it is
 * left out everywhere. Totals are in cents, like the rest of the app.
 *
 * Grouping by day happens in PHP rather than SQL so it behaves the same on
 * MySQL in production and SQLite in the tests.
 */
class SalesReport
{
    /** @return array{revenue: int, orders: int} */
    public static function between(CarbonInterface $from, CarbonInterface $to): array
    {
        $orders = Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from, $to])
            ->get(['total']);

        return ['revenue' => (int) $orders->sum('total'), 'orders' => $orders->count()];
    }

    public static function today(): array
    {
        return self::between(now()->startOfDay(), now()->endOfDay());
    }

    public static function yesterday(): array
    {
        return self::between(now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
    }

    public static function thisMonth(): array
    {
        return self::between(now()->startOfMonth(), now()->endOfDay());
    }

    /**
     * One entry per day, oldest first, zero-filled so the chart has no gaps.
     *
     * @return array<string, array{revenue: int, orders: int}>  keyed by Y-m-d
     */
    public static function daily(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $series[$start->copy()->addDays($i)->toDateString()] = ['revenue' => 0, 'orders' => 0];
        }

        Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start)
            ->get(['total', 'created_at'])
            ->each(function (Order $order) use (&$series) {
                $day = Carbon::parse($order->created_at)->toDateString();
                if (isset($series[$day])) {
                    $series[$day]['revenue'] += $order->total;
                    $series[$day]['orders']++;
                }
            });

        return $series;
    }

    /** "+12%" / "-5%" against a previous figure, or null when there is nothing to compare to. */
    public static function change(int $current, int $previous): ?string
    {
        if ($previous <= 0) {
            return null;
        }

        $pct = (int) round((($current - $previous) / $previous) * 100);

        return ($pct >= 0 ? '+' : '').$pct.'%';
    }
}
