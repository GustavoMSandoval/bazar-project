<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sale extends Model
{
    protected $fillable = [
        'total',
        'sale_date', 
        'sale_time',
        'payment_method', 
        'amount_received', 
        'change_amount',
        'customer_name'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'sale_products')
            ->withPivot('quantity', 'value', 'total')
            ->withTimestamps();
    }

    public function calculateTotal(): void
    {
        $total = $this->products->sum(fn ($product) => $product->pivot->total);
        $this->update(['total' => $total]);
    }

}
