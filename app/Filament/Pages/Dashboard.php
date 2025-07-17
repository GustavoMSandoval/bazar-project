<?php 

namespace App\Filament\Pages;

use App\Models\Owner;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Form;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Filtros')->schema([
                Select::make('owner_id')
                    ->label('Proprietário')
                    ->options(Owner::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->required(),

                Select::make('month')
                    ->label('Mês')
                    ->options([
                        '01' => 'Janeiro',
                        '02' => 'Fevereiro',
                        '03' => 'Março',
                        '04' => 'Abril',
                        '05' => 'Maio',
                        '06' => 'Junho',
                        '07' => 'Julho',
                        '08' => 'Agosto',
                        '09' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro',
                    ])
                    ->default(now()->format('m'))
                    ->required(),

                Select::make('year')
                    ->label('Ano')
                    ->options($this->getYearOptions())
                    ->default(now()->year)
                    ->required(),
            ])->columns(3)
        ]);
    }

    protected function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = $year;
        }

        return $years;
    }
}
