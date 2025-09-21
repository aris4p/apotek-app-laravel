<?php

namespace App\Http\Controllers;

use App\Models\Stock_movements;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockMovementsController extends Controller
{
    public function index()
    {
        $stockMovements = Stock_movements::with(['product', 'user'])->orderBy('movement_date', 'desc')->get();
        return response()->json([
            'message' => 'Stock movements retrieved successfully',
            'data' => $stockMovements
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'movement_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reference_type' => 'required|in:sale,purchase,adjustment,return',
            'reference_id' => 'nullable|integer',
            'batch_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'movement_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $product = Products::find($request->product_id);

            // Validate stock availability for 'out' movements
            if ($request->movement_type === 'out' && $product->stock < $request->quantity) {
                return response()->json([
                    'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}, Required: {$request->quantity}"
                ], 400);
            }

            // Create stock movement
            $stockMovement = Stock_movements::create([
                'product_id' => $request->product_id,
                'movement_type' => $request->movement_type,
                'quantity' => $request->quantity,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'batch_number' => $request->batch_number,
                'expiry_date' => $request->expiry_date,
                'notes' => $request->notes,
                'user_id' => $request->user()->id,
                'movement_date' => $request->movement_date ?? now(),
            ]);

            // Update product stock based on movement type
            switch ($request->movement_type) {
                case 'in':
                    $product->increment('stock', $request->quantity);
                    break;
                case 'out':
                    $product->decrement('stock', $request->quantity);
                    break;
                case 'adjustment':
                    // For adjustment, we need to calculate the difference
                    if ($request->has('new_stock_amount')) {
                        $currentStock = $product->stock;
                        $newStock = $request->new_stock_amount;
                        $difference = $newStock - $currentStock;

                        $product->update(['stock' => $newStock]);

                        // Update the stock movement quantity to reflect the actual adjustment
                        $stockMovement->update([
                            'quantity' => abs($difference),
                            'movement_type' => $difference >= 0 ? 'in' : 'out'
                        ]);
                    }
                    break;
            }

            $stockMovement->load(['product', 'user']);

            DB::commit();

            return response()->json([
                'message' => 'Stock movement created successfully',
                'data' => $stockMovement
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create stock movement: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $stockMovement = Stock_movements::with(['product', 'user'])->find($id);

        if (!$stockMovement) {
            return response()->json([
                'message' => 'Stock movement not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Stock movement retrieved successfully',
            'data' => $stockMovement
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $stockMovement = Stock_movements::find($id);

        if (!$stockMovement) {
            return response()->json([
                'message' => 'Stock movement not found'
            ], 404);
        }

        // Only allow updating notes for security reasons
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $stockMovement->update($request->only(['notes']));
        $stockMovement->load(['product', 'user']);

        return response()->json([
            'message' => 'Stock movement updated successfully',
            'data' => $stockMovement
        ], 200);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'Stock movements cannot be deleted for audit trail purposes'
        ], 403);
    }

    // Additional method to get stock movements by product
    public function getByProduct($productId)
    {
        $product = Products::find($productId);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $stockMovements = Stock_movements::with(['user'])
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'desc')
            ->get();

        return response()->json([
            'message' => 'Product stock movements retrieved successfully',
            'data' => [
                'product' => $product,
                'stock_movements' => $stockMovements,
                'current_stock' => $product->stock
            ]
        ], 200);
    }
}
