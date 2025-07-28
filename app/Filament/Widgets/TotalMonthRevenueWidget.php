<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class TotalMonthRevenueWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Lucro Diário Total do Mês';

    protected function getData(): array
    {
        $month = $this->filters['month'] ?? null;
        $year = $this->filters['year'] ?? null;

        if (!$month || !$year) {
            return [
                'datasets' => [['label' => 'Lucro Diário', 'data' => []]],
                'labels' => [],
            ];
        }

        $start = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $results = DB::table('sale_products')
            ->join('sales', 'sale_products.sale_id', '=', 'sales.id')
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
            'labels' => $results->map(fn($row) => Carbon::parse($row->sale_day)->format('d/m/Y')),
        ];
    }

    public function getType(): string
    {
        return 'bar';
    }
}
