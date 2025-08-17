<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->string('unit')->comment('e.g., pcs, box, bottle');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_prescription')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
