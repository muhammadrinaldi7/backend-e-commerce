<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\CheckAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    use CheckAdmin;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $category = Category::get();
        if($category->isEmpty()) {
           return ResponseHelper::error('No category found', 404);
        }
        return ResponseHelper::success($category, 'Category retrieved successfully',201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        if($error = $this->checkIfAdmin()) {
            return $error;
        }
        $validasi = Validator::make($request->all(), [
            'category_name' => 'required|string|unique:categories|max:255',
        ]);
        if ($validasi->fails()) {
            return ResponseHelper::error($validasi->errors(), 422);
        }
        $category = Category::create([
            'category_name' => $request->category_name,
        ]);
        if (!$category) {
            return ResponseHelper::error('Failed to create category', 500, $category);
        }
        return ResponseHelper::success($category, 'Category created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        if($error = $this->checkIfAdmin()) {
            return $error;
        }
        $category = Category::with('products')->find($id);
        if (!$category) {
            return ResponseHelper::error('Category not found', 404);
        }
        if ($category->products->count() > 0) {
            return ResponseHelper::error('Cannot delete category. It still has products.', 400);
        }
        $deleted = $category->delete();
        if($deleted) {
            return ResponseHelper::success(null, 'Category deleted successfully', 200);
        } else {
            return ResponseHelper::error('Failed to delete category', 500);
        }
    }
}
