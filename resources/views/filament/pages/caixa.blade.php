<x-filament-panels::page>
    
    
    <form wire:submit.prevent="submit">
        <div class="my-2">{{ $this->form }}</div>
        <x-filament::button
        type="submit"
        wire:loading.attr="disabled"         
        >
        @if ($isSubmitting)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processando...
        @else
        Finalizar Venda
        @endif
        </x-filament::button>
        {{-- Botão que abre o modal de histórico --}}
        <x-filament::button color="gray" class="my-4" wire:click="$dispatch('open-modal', { id: 'historico-modal' })">
            Histórico de Vendas
        </x-filament::button>
    </form>

    <x-filament::modal id="modal">
        <x-slot name="heading">
            Comprovante de Venda #{{ $sale->id ?? '' }}
        </x-slot>

        @if(isset($sale))
        <div class="space-y-4" id="printContent">
                <div class="flex justify-between">
                    <span>Data:</span>
                    <span>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }} {{ $sale->sale_time }}</span>
                </div>

                @if($sale->customer_name)
                    <div class="flex justify-between">
                        <span>Cliente:</span>
                        <span>{{ $sale->customer_name }}</span>
                    </div>
                @endif

                <div class="border-t border-gray-200 pt-4">
                    @foreach($sale->products as $product)
                    <div class="flex justify-between py-2">
                        <span>{{ $product->name }} x{{ $product->pivot->quantity }}</span>
                            <span>R$ {{ number_format($product->price, 2, ',', '.') }}</span>
                        </div>
                        <div class="text-right text-sm text-gray-500">
                            Subtotal: R$ {{ number_format($product->pivot->total, 2, ',', '.') }}
                        </div>
                    @endforeach
                </div>
                
                <div class="border-t border-gray-200 pt-4 font-bold">
                    <div class="flex justify-between">
                        <span>Total:</span>
                        <span>R$ {{ number_format($sale->total, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Forma de Pagamento:</span>
                        <span>
                            @switch($sale->payment_method)
                                @case('dinheiro') Dinheiro @break
                                @case('cartao_credito') Cartão de Crédito @break
                                @case('cartao_debito') Cartão de Débito @break
                                @case('pix') PIX @break
                                @case('transferencia') Transferência @break
                            @endswitch
                        </span>
                    </div>
                    @if($sale->payment_method === 'dinheiro')
                        <div class="flex justify-between">
                            <span>Valor Recebido:</span>
                            <span>R$ {{ number_format($sale->amount_received, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Troco:</span>
                            <span>R$ {{ number_format($sale->change_amount, 2, ',', '.') }}</span>
                        </div>
                        @endif
                </div>
            </div>
            @endif

            <x-slot name="footer">
                <x-filament::button wire:click="$dispatch('close-modal', {id: 'modal'});">
                    Fechar
                </x-filament::button>
                <x-filament::button color="success" onclick="window.print()">
                    Imprimir
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- MODAL: Histórico --}}
    <x-filament::modal id="historico-modal" width="5xl">
        <x-slot name="heading">
            Histórico de Vendas
        </x-slot>

        <div class="space-y-2">
            @foreach ($this->getSales() as $sale)
                <div class="flex justify-between items-center border-b py-2">
                    <div>
                        <div><strong>ID:</strong> {{ $sale->id }} | <strong>Data:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }} {{ $sale->sale_time }}</div>
                        <div><strong>Cliente:</strong> {{ $sale->customer_name ?? '---' }}</div>
                        <div><strong>Total:</strong> R$ {{ number_format($sale->total, 2, ',', '.') }}</div>
                        <div><strong>Pagamento:</strong>
                            @switch($sale->payment_method)
                                @case('dinheiro') Dinheiro @break
                                @case('cartao_credito') Cartão de Crédito @break
                                @case('cartao_debito') Cartão de Débito @break
                                @case('pix') PIX @break
                                @case('transferencia') Transferência @break
                            @endswitch
                        </div>
                    </div>
                    <div>
                        <x-filament::button size="sm" wire:click="openSalesHistory({{ $sale->id }})">
                            Visualizar / Imprimir
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>

        <x-slot name="footer">
            <x-filament::button wire:click="$dispatch('close-modal', { id: 'historico-modal' })">
                Fechar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #printContent, #printContent * {
                visibility: visible;
            }
            #printContent {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                font-size: 12.5px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</x-filament-panels::page>