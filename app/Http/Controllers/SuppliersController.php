<?php

namespace App\Http\Controllers;

use App\Models\Suppliers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuppliersController extends Controller
{
    public function index()
    {
        $suppliers = Suppliers::all();
        return response()->json([
            'message' => 'Suppliers retrieved successfully',
            'data' => $suppliers
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Suppliers::create([
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ], 201);
    }

    public function show($id)
    {
        $supplier = Suppliers::find($id);

        if (!$supplier) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Supplier retrieved successfully',
            'data' => $supplier
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $supplier = Suppliers::find($id);

        if (!$supplier) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier->update([
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->is_active ?? $supplier->is_active,
        ]);

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ], 200);
    }

    public function destroy($id)
    {
        $supplier = Suppliers::find($id);

        if (!$supplier) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }

        // Check if supplier has related products or purchases
        if ($supplier->products()->count() > 0 || $supplier->purchases()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete supplier because it has related products or purchases'
            ], 400);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully'
        ], 200);
    }
}
