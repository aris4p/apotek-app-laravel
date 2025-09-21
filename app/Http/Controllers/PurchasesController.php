<?php

namespace App\Http\Controllers;

use App\Models\Purchases;
use App\Models\Purchase_details;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PurchasesController extends Controller
{
    public function index()
    {
        $purchases = Purchases::with(['supplier', 'user', 'purchaseDetails.product'])->get();
        return response()->json([
            'message' => 'Purchases retrieved successfully',
            'data' => $purchases
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'nullable|date',
            'status' => 'required|in:pending,received,cancelled',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate purchase number
            $purchaseNumber = 'PO-' . date('Ymd') . '-' . str_pad(Purchases::count() + 1, 4, '0', STR_PAD_LEFT);

            // Calculate totals
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
            }

            $discountAmount = $request->discount_amount ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $finalAmount = $totalAmount - $discountAmount + $taxAmount;

            // Create purchase
            $purchase = Purchases::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $request->supplier_id,
                'user_id' => $request->user()->id,
                'purchase_date' => $request->purchase_date ?? now(),
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // Create purchase details and update stock if status is 'received'
            foreach ($request->items as $item) {
                // Create purchase detail
                Purchase_details::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_price' => ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                // Update product stock only if purchase is received
                if ($request->status === 'received') {
                    $product = Products::find($item['product_id']);
                    $product->increment('stock', $item['quantity']);

                    // Update product details if provided
                    if (isset($item['batch_number']) || isset($item['expiry_date'])) {
                        $updateData = [];
                        if (isset($item['batch_number'])) {
                            $updateData['batch_number'] = $item['batch_number'];
                        }
                        if (isset($item['expiry_date'])) {
                            $updateData['expiry_date'] = $item['expiry_date'];
                        }
                        $product->update($updateData);
                    }
                }
            }

            $purchase->load(['supplier', 'user', 'purchaseDetails.product']);

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $purchase = Purchases::with(['supplier', 'user', 'purchaseDetails.product'])->find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Purchase retrieved successfully',
            'data' => $purchase
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchases::find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Handle status change logic
            if ($request->has('status') && $request->status !== $purchase->status) {
                if ($request->status === 'received' && $purchase->status === 'pending') {
                    // Add stock when changing from pending to received
                    foreach ($purchase->purchaseDetails as $detail) {
                        $product = Products::find($detail->product_id);
                        $product->increment('stock', $detail->quantity);
                    }
                } elseif ($request->status === 'cancelled' && $purchase->status === 'received') {
                    // Remove stock when changing from received to cancelled
                    foreach ($purchase->purchaseDetails as $detail) {
                        $product = Products::find($detail->product_id);
                        if ($product->stock >= $detail->quantity) {
                            $product->decrement('stock', $detail->quantity);
                        }
                    }
                }
            }

            $purchase->update($request->only(['status', 'notes']));
            $purchase->load(['supplier', 'user', 'purchaseDetails.product']);

            DB::commit();

            return response()->json([
                'message' => 'Purchase updated successfully',
                'data' => $purchase
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $purchase = Purchases::find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found'
            ], 404);
        }

        // Only allow deletion if status is pending
        if ($purchase->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete purchase with status: ' . $purchase->status
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Delete purchase details
            $purchase->purchaseDetails()->delete();

            // Delete purchase
            $purchase->delete();

            DB::commit();

            return response()->json([
                'message' => 'Purchase deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete purchase: ' . $e->getMessage()
            ], 500);
        }
    }
}
