<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Filament\Resources\SaleResource\RelationManagers\ProductsRelationManager;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $modelLabel = 'Vendas';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('customer_name')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('total')
                    ->searchable(),
                TextColumn::make('sale_date')
                    ->searchable()
                    ->date('d/m/Y'),
                TextColumn::make('sale_time')
                    ->searchable()
                    ->time('H:i'),
                TextColumn::make('payment_method')
                    ->searchable(),
                TextColumn::make('amount_received')
                    ->searchable(),
                TextColumn::make('change_amount')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->default("Sem nome"),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool { return false;}
}
