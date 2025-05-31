<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $counter = [
            'product' => Product::count(),
            'category' => Category::count(),
            'order' => Order::count(),
            'notDelivered' => Payment::with('orders')->where('payment_status', '=' ,'PAID', 'AND' , 'order_status', '!=', 'DELIVERED' )->count(),
            'user' => User::count(),
        ];

        return ResponseHelper::success($counter, 'Dashboard retrieved successfully', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
