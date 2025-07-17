<?php

namespace App\Filament\Widgets;

use App\Models\Owner;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OwnerMonthRevenueWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Lucro Diário por Proprietário';

    protected function getData(): array
    {
        $ownerId = $this->filters['owner_id'] ?? null;
        $month = $this->filters['month'] ?? null;
        $year = $this->filters['year'] ?? null;

        if (!$ownerId || !$month || !$year) {
            return [
                'datasets' => [['label' => 'Lucro Diário', 'data' => []]],
                'labels' => [],
            ];
        }

        $start = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $results = DB::table('sale_products')
            ->join('sales', 'sale_products.sale_id', '=', 'sales.id')
            ->where('sale_products.owner_id', $ownerId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->selectRaw('DATE(sales.sale_date) as sale_day, SUM(sale_products.total) as total')
            ->groupBy('sale_day')
            ->orderBy('sale_day')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Lucro Diário',
                    'data' => $results->pluck('total'),
                ],
            ],
            'labels' => $results->pluck('sale_day'),
        ];
    }

    public function getType(): string
    {
        return 'bar';
    }
}
