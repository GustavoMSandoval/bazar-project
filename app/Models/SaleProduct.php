<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleProduct extends Model
{

    protected $fillable = [
        'product_id',
        'sale_id',
        'owner_id',
        'quantity',
        'value',
        'total'
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
