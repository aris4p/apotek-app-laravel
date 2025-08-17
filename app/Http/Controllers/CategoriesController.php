<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categories;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = Categories::all();
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ],200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Categories::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    public function show($id)
    {
        $category = Categories::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => $category
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $category = Categories::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ], 200);
    }

    public function destroy($id)
    {
        $category = Categories::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
