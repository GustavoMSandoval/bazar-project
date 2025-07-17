<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $fillable = ['name', 'phone', 'cpf', 'rg'];
    
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class);
    }
    
    public function soldProducts(): HasMany
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,    
            Barcode::class,    
            'owner_id',                    
            'barcode_id',                  
            'id',                        
            'id'                           
        );
    }
}
