<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\CheckAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    use CheckAdmin;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product = Product::with('category')->get();
        if($product->isEmpty()) {
           return ResponseHelper::error('No products found', 404);
        }
        return ResponseHelper::success($product, 'Products retrieved successfully',201);
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
            'product_name' => 'required|string|max:255',
            'image_product' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);
        if ($validasi->fails()) {
            return ResponseHelper::error($validasi->errors(), 422);
        }
        // Handle image upload
        if ($request->hasFile('image_product')) {
            $imagePath = $request->file('image_product')->store('products', 'public');
        } else {
            $imagePath = null; 
        }
        $product = Product::create([
            'product_name' => $request->product_name,
            'image_product' => $imagePath,
            'price' => $request->price,
            'qty' => $request->qty,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ]);
        if (!$product) {
            return ResponseHelper::error('Failed to create product', 500, $product);
        }
        return ResponseHelper::success($product, 'Product created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->find($id);
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }
        return ResponseHelper::success($product, 'Product retrieved successfully', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ($error = $this->checkIfAdmin()) {
            return $error;
        }
    
        $product = Product::find($id);
    
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }
    
        $validated = Validator::make($request->all(), [
            'product_name' => 'sometimes|string|max:255',
            'image_product' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'sometimes|numeric|min:0',
            'qty' => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
        ]);
    
        if ($validated->fails()) {
            return ResponseHelper::error($validated->errors(), 422);
        }
    
        $data = array_filter($validated->validated(), function ($value) {
            return $value !== null; // biar bisa tetap update angka 0
        });
    
        // Handle image update
        if ($request->hasFile('image_product')) {
            if ($product->image_product && Storage::disk('public')->exists($product->image_product)) {
                Storage::disk('public')->delete($product->image_product);
            }
            $data['image_product'] = $request->file('image_product')->store('products', 'public');
        }
    
        $product->update($data);
    
        return ResponseHelper::success($product, 'Product updated successfully', 200);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if ($error = $this->checkIfAdmin()) {
            return $error;
        }
    
        $product = Product::with('detailOrders')->find($id);
    
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }   
    
        if ($product->detailOrders->isNotEmpty()) {
            return ResponseHelper::error('Product telah ada di transaksi', 422);
        }
        
        // Delete the image file if it exists
        if ($product->image_product && Storage::disk('public')->exists($product->image_product)) {
            Storage::disk('public')->delete($product->image_product);
        }
        if ($product->gallery_product) {
            $gallery = json_decode($product->gallery_product, true);
            foreach ($gallery as $image) {
                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }
        }
        $product->delete();
    
        return ResponseHelper::success(null, 'Product deleted successfully', 200);
    }

    public function addGallery(Request $request, string $id)
    {
        if($error = $this->checkIfAdmin()) {
            return $error;
        }
        $product = Product::find($id);
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }
        $validasi = Validator::make($request->all(), [
            'gallery_product' => 'required|array',
            'gallery_product.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validasi->fails()) {
            return ResponseHelper::error($validasi->errors(), 422);
        }
        $galleryPaths = [];
        foreach ($request->file('gallery_product') as $file) {
            $galleryPaths[] = $file->store('products/gallery', 'public');
        }
        $product->gallery_product = json_encode($galleryPaths);
        $product->save();
        return ResponseHelper::success($product, 'Gallery updated successfully', 200);
    }

    public function deleteGallery(string $id)
    {
        if($error = $this->checkIfAdmin()) {
            return $error;
        }
        $product = Product::find($id);
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }
        if ($product->gallery_product && Storage::disk('public')->exists($product->gallery_product)) {
            $gallery = json_decode($product->gallery_product, true);
            foreach ($gallery as $image) {
                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }
        }
        $product->gallery_product = null;
        $product->save();
        return ResponseHelper::success(null, 'Gallery deleted successfully', 200);
    }
    public function getGallery(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return ResponseHelper::error('Product not found', 404);
        }
        $gallery = json_decode($product->gallery_product, true);
        if (empty($gallery)) {
            return ResponseHelper::error('No gallery images found', 404);
        }
        return ResponseHelper::success($gallery, 'Gallery retrieved successfully', 200);
    }

    public function filterByCategory(Request $request, string $categoryId)
    {
        $products = Product::where('category_id', $categoryId)->with('category')->get();
        if ($products->isEmpty()) {
            return ResponseHelper::error('No products found for this category', 404);
        }
        return ResponseHelper::success($products, 'Products retrieved successfully', 200);
    }
    public function search(Request $request)
    {
        $query = $request->input('query');
        if (!$query) {
            return ResponseHelper::error('Query parameter is required', 400);
        }
        $products = Product::where('product_name', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->with('category')
            ->get();
        if ($products->isEmpty()) {
            return ResponseHelper::error('No products found for this search', 404);
        }
        return ResponseHelper::success($products, 'Products retrieved successfully', 200);
    }
}
