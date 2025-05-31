<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Detail_order;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::guard('sanctum')->user();
    
        $orders = Order::with(['details.product','payment'])
            ->where('user_id', $user->id) 
            ->orderBy('order_date', 'desc')
            ->get();
    
        if ($orders->isEmpty()) {
            return ResponseHelper::error('No orders found', 404);
        }
    
        return ResponseHelper::success($orders, 'Orders retrieved successfully', 200);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validasi = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'shipping_address' => 'required|string',
        'items' => 'required|array',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
    ]);
    if ($validasi->fails()) {
        return ResponseHelper::error($validasi->errors(), 422);
    }

    $totalPrice = 0;
    $order = Order::create([
        'user_id' => $request->user_id,
        'order_date' => now(),
        'shipping_address' => $request->shipping_address,
        'status' => 'pending',
        'total_price' => 0, //sementara total_price diisi 0, akan diupdate setelah detail_order dibuat
    ]);

    foreach ($request->items as $item) {
        $product = Product::find($item['product_id']);
        $subtotal = $product->price * $item['quantity'];
        $totalPrice += $subtotal;

        Detail_order::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $product->price,
        ]);
    }

    $order->update(['total_price' => $totalPrice]);
    $dataOrder = ['order_id' => $order->id, 'total_price' => $totalPrice];
    return ResponseHelper::success($dataOrder, 'Order created successfully', 201);
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
    }

}
