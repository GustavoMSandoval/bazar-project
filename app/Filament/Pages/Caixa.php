<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

class Caixa extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.pages.caixa';
    protected static ?string $navigationLabel = 'Ponto de Venda';
    protected static ?string $title = 'Caixa';
    protected static ?int $navigationSort = 1;

    public bool $isSubmitting = false;
    public $products = [];
    public $total = 0.00;
    public $sale;
    public $payment_method = 'dinheiro';
    public $customer_name = '';
    public $amount_received = 0.00;
    public $change_amount = 0.00;

    public function mount(): void
    {
         $this->form->fill([
            'total' => 0,
            'amount_received' => 0,
            'change_amount' => 0,
            'products' => [
                [
                    'product_id' => null,
                    'price' => 0,
                    'available_quantity' => 0,
                    'quantity' => 1,
                ]
            ],
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Informações do Cliente')
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Nome do Cliente')
                        ->placeholder('Opcional')
                        ->maxLength(255),
                ])->columns(1),

            Section::make('Produtos')
                ->schema([
                    Forms\Components\Repeater::make('products')
                        ->label('Itens da Venda')
                        ->schema([
                            Select::make('product_id')
                                ->label('Produto')
                                ->options(Product::where('quantity', '>', 0)->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('price', $product->price);
                                        $set('available_quantity', $product->quantity);
                                    }

                                    // Garante que o total seja recalculado após selecionar o produto
                                    $this->updateTotal();
                                }),

                            TextInput::make('price')
                                ->label('Preço Unitário')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('available_quantity')
                                ->label('Em Estoque')
                                ->numeric()
                                ->disabled(),

                            TextInput::make('quantity')
                                ->label('Quantidade')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $available = $get('available_quantity') ?? 0;
                                    if ($state > $available) {
                                        Notification::make()
                                            ->title('Quantidade indisponível')
                                            ->warning()
                                            ->send();
                                        $set('quantity', $available);
                                    }
                                    $this->updateTotal();
                                }),
                        ])
                        ->columns(4)
                        ->required()
                        ->addActionLabel('Adicionar Produto')
                        ->orderColumn()
                        ->collapsible(),
                ]),

            Section::make('Pagamento')
                ->schema([
                    Select::make('payment_method')
                        ->label('Forma de Pagamento')
                        ->options([
                            'dinheiro' => 'Dinheiro',
                            'cartao_credito' => 'Cartão de Crédito',
                            'cartao_debito' => 'Cartão de Débito',
                            'pix' => 'PIX',
                            'transferencia' => 'Transferência',
                        ])
                        ->required()
                        ->default('dinheiro')
                        ->reactive(),

                    TextInput::make('total')
                        ->label('Total da Venda')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),

                    TextInput::make('amount_received')
                        ->label('Valor Recebido')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->visible(fn (callable $get) => $get('payment_method') === 'dinheiro')
                        ->dehydrated(true),

                    TextInput::make('change_amount')
                        ->label('Troco')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->visible(fn (callable $get) => $get('payment_method') === 'dinheiro')
                        ->dehydrated(true),
                ])->columns(2),
        ];
    }

    protected function updateTotal(): void
    {
        $data = $this->form->getState();
        $total = collect($data['products'] ?? [])->sum(fn ($item) => 
            (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1)
        );
        
        // Atualiza o total e reseta o valor recebido e troco
        $this->form->fill([
            'total' => $total,
            'amount_received' => $total, // Define o valor recebido como o total por padrão
            'change_amount' => 0 // Reseta o troco
        ]);
    }

    public function getSales()
    {
        return Sale::latest()->limit(50)->get(); // ou personalize
    }

    public function openSalesHistory($saleId)
    {
        $this->sale = Sale::with('products')->findOrFail($saleId);

        // Fecha modal de histórico e abre o modal de comprovante
        $this->dispatch('close-modal', id: 'historico-modal');
        $this->dispatch('open-modal', id: 'modal');
    }

    public function submit(): void
    {
        $this->isSubmitting = true;
        $data = $this->form->getState();
        $now = Carbon::now();

        // Remove produtos com quantidade 0
        $validProducts = collect($data['products'])->filter(fn ($item) => $item['quantity'] > 0)->values();

        if ($validProducts->isEmpty()) {
            Notification::make()
                ->title('Nenhum produto válido')
                ->body('Você precisa adicionar pelo menos um produto com quantidade maior que zero.')
                ->warning()
                ->send();

            $this->form->fill([
                ...$data,
                'products' => [], // Limpa os produtos inválidos do formulário
                'total' => 0,
                'amount_received' => 0,
                'change_amount' => 0,
            ]);

            $this->isSubmitting = false;
            return;
        }

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'total' => $data['total'],
                'sale_date' => $now->toDateString(),
                'sale_time' => $now->toTimeString(),
                'payment_method' => $data['payment_method'],
                'customer_name' => $data['customer_name'] ?? null,
                'amount_received' => $data['amount_received'] ?? $data['total'],
                'change_amount' => $data['change_amount'] ?? 0,
            ]);

            foreach ($validProducts as $item) {
                $product = Product::find($item['product_id']);

                $ownerId = $product->barcode->owner_id;

                $sale->products()->attach($product->id, [
                    'owner_id' => $ownerId,
                    'quantity' => $item['quantity'],
                    'value' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                $product->decrement('quantity', $item['quantity']);
            }

            DB::commit();

            $this->sale = Sale::with('products')->find($sale->id);

            $this->dispatch('open-modal', id: 'modal');

            // Resetar formulário com produtos limpos
            $this->form->fill([
                'customer_name' => '',
                'payment_method' => 'dinheiro',
                'products' => [],
                'total' => 0,
                'amount_received' => 0,
                'change_amount' => 0,
            ]);

            Notification::make()
                ->title('Venda registrada com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Erro ao processar venda')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSubmitting = false;
        }
    }

    protected function getFormModel(): Sale
    {
        return new Sale();
    }
}