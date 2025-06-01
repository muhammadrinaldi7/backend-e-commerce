<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Detail_order;
use App\Models\Order;
use App\Models\Product;
use App\Traits\CheckAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{   
    use CheckAdmin;
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
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return ResponseHelper::error('Order not found', 404);
        }
        $order->delete();
        return ResponseHelper::success(null, 'Order deleted successfully', 200);
    }

    public function updateStatusOrder(Request $request, string $id)
    {   
        if($error = $this->checkIfAdmin()) {
            return $error;
        }
        $validasi = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,canceled',
        ]);
        if ($validasi->fails()) {
            return ResponseHelper::error($validasi->errors(), 422);
        }
        $order = Order::find($id);
        if (!$order) {
            return ResponseHelper::error('Order not found', 404);
        }
        $order->status = $request->status;
        $order->save();
        return ResponseHelper::success($order, 'Order status updated successfully', 200);
    }

    public function getAllOrders()
    {
        $orders = Order::with('user', 'payment')->get();
        if ($orders->isEmpty()) {
            return ResponseHelper::error('No orders found', 404);
        }

        return ResponseHelper::success($orders, 'Orders retrieved successfully', 200);
    }
}
