<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    public function index()
    {
        $products = Products::with(['category', 'supplier'])->get();
        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:products,code',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'batch_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_prescription' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Products::create([
            'code' => $request->code,
            'name' => $request->name,
            'generic_name' => $request->generic_name,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'unit' => $request->unit,
            'price' => $request->price,
            'stock' => $request->stock,
            'minimum_stock' => $request->minimum_stock,
            'expiry_date' => $request->expiry_date,
            'batch_number' => $request->batch_number,
            'description' => $request->description,
            'is_prescription' => $request->is_prescription ?? false,
            'is_active' => $request->is_active ?? true,
        ]);

        $product->load(['category', 'supplier']);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    public function show($id)
    {
        $product = Products::with(['category', 'supplier'])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $product = Products::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:products,code,' . $id,
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'batch_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_prescription' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update([
            'code' => $request->code,
            'name' => $request->name,
            'generic_name' => $request->generic_name,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'unit' => $request->unit,
            'price' => $request->price,
            'stock' => $request->stock,
            'minimum_stock' => $request->minimum_stock,
            'expiry_date' => $request->expiry_date,
            'batch_number' => $request->batch_number,
            'description' => $request->description,
            'is_prescription' => $request->is_prescription ?? $product->is_prescription,
            'is_active' => $request->is_active ?? $product->is_active,
        ]);

        $product->load(['category', 'supplier']);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    public function destroy($id)
    {
        $product = Products::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        // Check if product is used in sales or purchases before deleting
        if ($product->saleDetails()->count() > 0 || $product->purchaseDetails()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete product because it has related transactions'
            ], 400);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
