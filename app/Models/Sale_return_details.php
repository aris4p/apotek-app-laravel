<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale_return_details extends Model
{
    protected $fillable = [
        'return_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function return()
    {
        return $this->belongsTo(Sale_returns::class, 'return_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
