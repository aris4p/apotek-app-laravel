<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\Sale_details;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index()
    {
        $sales = Sales::with(['customer', 'user', 'saleDetails.product'])->get();
        return response()->json([
            'message' => 'Sales retrieved successfully',
            'data' => $sales
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'nullable|date',
            'payment_method' => 'required|in:cash,transfer',
            'payment_status' => 'required|in:pending,paid,cancelled',
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
            // Generate sale number
            $saleNumber = 'SL-' . date('Ymd') . '-' . str_pad(Sales::count() + 1, 4, '0', STR_PAD_LEFT);

            // Calculate totals
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
            }

            $discountAmount = $request->discount_amount ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $finalAmount = $totalAmount - $discountAmount + $taxAmount;

            // Create sale
            $sale = Sales::create([
                'sale_number' => $saleNumber,
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'sale_date' => $request->sale_date ?? now(),
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'notes' => $request->notes,
            ]);

            // Create sale details and update stock
            foreach ($request->items as $item) {
                $product = Products::find($item['product_id']);

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->stock}, Required: {$item['quantity']}");
                }

                // Create sale detail
                Sale_details::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_price' => ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                // Update product stock
                $product->decrement('stock', $item['quantity']);
            }

            $sale->load(['customer', 'user', 'saleDetails.product']);

            DB::commit();

            return response()->json([
                'message' => 'Sale created successfully',
                'data' => $sale
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $sale = Sales::with(['customer', 'user', 'saleDetails.product'])->find($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Sale retrieved successfully',
            'data' => $sale
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $sale = Sales::find($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found'
            ], 404);
        }

        // Only allow updating certain fields for business logic reasons
        $validator = Validator::make($request->all(), [
            'payment_status' => 'sometimes|required|in:pending,paid,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $sale->update($request->only(['payment_status', 'notes']));

        $sale->load(['customer', 'user', 'saleDetails.product']);

        return response()->json([
            'message' => 'Sale updated successfully',
            'data' => $sale
        ], 200);
    }

    public function destroy($id)
    {
        $sale = Sales::find($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found'
            ], 404);
        }

        // Only allow deletion if payment is pending
        if ($sale->payment_status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete sale with payment status: ' . $sale->payment_status
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Restore stock for each sale detail
            foreach ($sale->saleDetails as $detail) {
                $product = Products::find($detail->product_id);
                $product->increment('stock', $detail->quantity);
            }

            // Delete sale details
            $sale->saleDetails()->delete();

            // Delete sale
            $sale->delete();

            DB::commit();

            return response()->json([
                'message' => 'Sale deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete sale: ' . $e->getMessage()
            ], 500);
        }
    }
}
