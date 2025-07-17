<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barcode extends Model
{
    protected $fillable = ['owner_id', 'code'];
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
