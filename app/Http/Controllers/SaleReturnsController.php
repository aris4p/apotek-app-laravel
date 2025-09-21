<?php

namespace App\Http\Controllers;

use App\Models\Sale_returns;
use App\Models\Sale_return_details;
use App\Models\Sales;
use App\Models\Sale_details;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SaleReturnsController extends Controller
{
    public function index()
    {
        $returns = Sale_returns::with(['sale', 'customer', 'user', 'returnDetails.product'])->get();
        return response()->json([
            'message' => 'Sale returns retrieved successfully',
            'data' => $returns
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'nullable|date',
            'reason' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $sale = Sales::with('saleDetails')->find($request->sale_id);

            // Generate return number
            $returnNumber = 'RT-' . date('Ymd') . '-' . str_pad(Sale_returns::count() + 1, 4, '0', STR_PAD_LEFT);

            // Validate return quantities
            foreach ($request->items as $item) {
                $saleDetail = $sale->saleDetails->where('product_id', $item['product_id'])->first();

                if (!$saleDetail) {
                    throw new \Exception("Product ID {$item['product_id']} was not found in the original sale");
                }

                // Check if return quantity exceeds sold quantity
                $alreadyReturned = Sale_return_details::whereHas('return', function($query) use ($request) {
                    $query->where('sale_id', $request->sale_id)
                          ->where('status', '!=', 'rejected');
                })->where('product_id', $item['product_id'])->sum('quantity');

                if (($alreadyReturned + $item['quantity']) > $saleDetail->quantity) {
                    throw new \Exception("Return quantity for product exceeds available quantity. Available for return: " . ($saleDetail->quantity - $alreadyReturned));
                }
            }

            // Calculate total return amount
            $totalReturnAmount = 0;
            foreach ($request->items as $item) {
                $saleDetail = $sale->saleDetails->where('product_id', $item['product_id'])->first();
                $totalReturnAmount += $saleDetail->unit_price * $item['quantity'];
            }

            // Create sale return
            $saleReturn = Sale_returns::create([
                'return_number' => $returnNumber,
                'sale_id' => $request->sale_id,
                'customer_id' => $sale->customer_id,
                'user_id' => $request->user()->id,
                'return_date' => $request->return_date ?? now(),
                'total_return_amount' => $totalReturnAmount,
                'reason' => $request->reason,
                'status' => $request->status,
            ]);

            // Create return details and update stock if approved
            foreach ($request->items as $item) {
                $saleDetail = $sale->saleDetails->where('product_id', $item['product_id'])->first();

                Sale_return_details::create([
                    'return_id' => $saleReturn->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $saleDetail->unit_price,
                    'total_price' => $saleDetail->unit_price * $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ]);

                // Update product stock if return is approved
                if ($request->status === 'approved') {
                    $product = Products::find($item['product_id']);
                    $product->increment('stock', $item['quantity']);
                }
            }

            $saleReturn->load(['sale', 'customer', 'user', 'returnDetails.product']);

            DB::commit();

            return response()->json([
                'message' => 'Sale return created successfully',
                'data' => $saleReturn
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create sale return: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $saleReturn = Sale_returns::with(['sale', 'customer', 'user', 'returnDetails.product'])->find($id);

        if (!$saleReturn) {
            return response()->json([
                'message' => 'Sale return not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Sale return retrieved successfully',
            'data' => $saleReturn
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $saleReturn = Sale_returns::find($id);

        if (!$saleReturn) {
            return response()->json([
                'message' => 'Sale return not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Handle status change logic
            if ($request->has('status') && $request->status !== $saleReturn->status) {
                if ($request->status === 'approved' && $saleReturn->status === 'pending') {
                    // Add stock when changing from pending to approved
                    foreach ($saleReturn->returnDetails as $detail) {
                        $product = Products::find($detail->product_id);
                        $product->increment('stock', $detail->quantity);
                    }
                } elseif ($request->status === 'rejected' && $saleReturn->status === 'approved') {
                    // Remove stock when changing from approved to rejected
                    foreach ($saleReturn->returnDetails as $detail) {
                        $product = Products::find($detail->product_id);
                        if ($product->stock >= $detail->quantity) {
                            $product->decrement('stock', $detail->quantity);
                        }
                    }
                }
            }

            $saleReturn->update($request->only(['status', 'reason']));
            $saleReturn->load(['sale', 'customer', 'user', 'returnDetails.product']);

            DB::commit();

            return response()->json([
                'message' => 'Sale return updated successfully',
                'data' => $saleReturn
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update sale return: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $saleReturn = Sale_returns::find($id);

        if (!$saleReturn) {
            return response()->json([
                'message' => 'Sale return not found'
            ], 404);
        }

        // Only allow deletion if status is pending
        if ($saleReturn->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete sale return with status: ' . $saleReturn->status
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Delete return details
            $saleReturn->returnDetails()->delete();

            // Delete sale return
            $saleReturn->delete();

            DB::commit();

            return response()->json([
                'message' => 'Sale return deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete sale return: ' . $e->getMessage()
            ], 500);
        }
    }
}
