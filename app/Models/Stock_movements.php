<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock_movements extends Model
{
    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'batch_number',
        'expiry_date',
        'notes',
        'user_id',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'movement_date' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Polymorphic relationship for reference
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
