<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale_returns extends Model
{
    protected $fillable = [
        'return_number',
        'sale_id',
        'customer_id',
        'user_id',
        'return_date',
        'total_return_amount',
        'reason',
        'status',
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'total_return_amount' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function returnDetails()
    {
        return $this->hasMany(Sale_return_details::class, 'return_id');
    }
}
