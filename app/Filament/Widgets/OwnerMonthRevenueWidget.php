<?php

namespace App\Filament\Widgets;

use App\Models\Owner;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OwnerMonthRevenueWidget extends ChartWidget
{

    use InteractsWithPageFilters;

    protected function getData(): array
    {

        $start = $this->filters['startDate'];
        $end = $this->filters['endDate'];

        $data = Trend::model(Owner::class)
            ->between(
                start: $start ? Carbon::parse($start) : now()->subMonths(6),
                end: $end ? Carbon::parse($end) : now(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => "Usuarios",
                    'data' => $data->map(fn (TrendValue $value ) => $value->aggregate)
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value ) => $value->date)
        ];
    }

    public function getType(): string 
    {
        return 'line';
    }
}
