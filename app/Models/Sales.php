<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $fillable = [
        'sale_number',
        'customer_id',
        'user_id',
        'sale_date',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(Sale_details::class, 'sale_id');
    }

    public function saleReturns()
    {
        return $this->hasMany(Sale_returns::class, 'sale_id');
    }
}
