<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ['barcode_id', 'name', 'quantity', 'price'];
    
    public function barcode(): BelongsTo
    {
        return $this->belongsTo(Barcode::class);
    }
    
    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(Sale::class, 'sale_products')
            ->withPivot('quantity', 'value', 'total')
            ->withTimestamps();
    }
}
