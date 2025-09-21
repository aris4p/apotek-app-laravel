<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase_details extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function purchase()
    {
        return $this->belongsTo(Purchases::class, 'purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
