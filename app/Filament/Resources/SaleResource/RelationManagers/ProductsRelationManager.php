<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use App\Filament\Resources\SaleResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('total')
            ->columns([
                Tables\Columns\TextColumn::make('CódBarras')
                    ->getStateUsing(fn ($record) => $record->barcode->code),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Quantidade'),
                Tables\Columns\TextColumn::make('Proprietário')
                    ->getStateUsing(fn ($record) => $record->barcode->owner->name),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('total'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                        ->action(function ($record, array $data): void {
                    $newQuantity = $data['quantity'];
                    $pivot = $record->pivot;
                    $previousQuantity = $pivot->quantity;
                    $unitPrice = $pivot->value / $previousQuantity;

                    $productModel = \App\Models\Product::find($record->id);

                    $availableStock = $productModel->quantity + $previousQuantity;

                    if ($newQuantity > $availableStock) {
                        Notification::make()
                            ->title('Estoque insuficiente')
                            ->body("Você tentou vender $newQuantity unidades, mas só há $availableStock disponíveis.")
                            ->danger()
                            ->send();
                        return;
                    }

                    $productModel->update([
                        'quantity' => $availableStock - $newQuantity,
                    ]);

                    DB::table('sale_products')
                        ->where('sale_id', $this->ownerRecord->id)
                        ->where('product_id', $record->id)
                        ->update([
                            'quantity' => $newQuantity,
                            'value' => $unitPrice * $newQuantity,
                        ]);

                    $this->ownerRecord->calculateTotal();

                    Notification::make()
                        ->title('Produto atualizado')
                        ->success()
                        ->send();
                }),
                Tables\Actions\DeleteAction::make() ->label('Remover da Venda')
                    ->modalHeading('Remover Produto da Venda')
                    ->modalDescription('Tem certeza que deseja remover este produto da venda?')
                    ->action(function ($record): void {
                        DB::transaction(function () use ($record) {
                            // Devolve a quantidade ao estoque
                            $record->increment('quantity', $record->pivot->quantity);

                            // Remove o produto da venda (pivot)
                            DB::table('sale_products')
                                ->where('sale_id', $this->ownerRecord->id)
                                ->where('product_id', $record->id)
                                ->delete();

                            // Verifica se ainda tem produtos na venda
                            $hasProducts = DB::table('sale_products')
                                ->where('sale_id', $this->ownerRecord->id)
                                ->exists();

                            if (!$hasProducts) {
                                $this->ownerRecord->delete();

                                Notification::make()
                                    ->title('Venda excluída por estar vazia')
                                    ->success()
                                    ->send();

                                $this->redirect(SaleResource::getUrl('index'), navigate: true);
                            } else {
                                $this->ownerRecord->calculateTotal();

                                Notification::make()
                                    ->title('Produto removido da venda')
                                    ->success()
                                    ->send();
                            }
                        })
                    ;}),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
