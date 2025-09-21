<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = [
        'code',
        'name',
        'generic_name',
        'category_id',
        'supplier_id',
        'unit',
        'price',
        'stock',
        'minimum_stock',
        'expiry_date',
        'batch_number',
        'description',
        'is_prescription',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'expiry_date' => 'date',
        'is_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(Sale_details::class, 'product_id');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(Purchase_details::class, 'product_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(Stock_movements::class, 'product_id');
    }
}
