<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class TotalMonthRevenueStat extends BaseWidget
{
    use InteractsWithPageFilters;

    public ?string $heading = "Lucro mensal Total";

    protected function getStats(): array
    {
        $month = $this->filters['month'] ?? null;
        $year = $this->filters['year'] ?? null;

        $monthlyRevenue = 0;

        if ($month && $year) {
            $start = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $monthlyRevenue = DB::table('sale_products')
                ->join('sales', 'sale_products.sale_id', '=', 'sales.id')
                ->whereBetween('sales.sale_date', [$start, $end])
                ->sum('sale_products.total');
        }

        return [
            Stat::make('Total', 'R$ ' . number_format($monthlyRevenue, 2, ',', '.')),
        ];
    }
    
}
