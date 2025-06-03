<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Detail_order;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\InvoiceCallback;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));

        $validasi = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
    
        if ($validasi->fails()) {
            return ResponseHelper::error($validasi->errors(), 422);
        }
        $PayOrder = Order::with('payment')->find($request->order_id);

        if ($PayOrder->payment->payment_status === 'PAID') {
            return ResponseHelper::error('Order sudah dibayar', 400);
        }

        if ($PayOrder->payment && $PayOrder->payment->payment_status === 'PENDING') {
            return ResponseHelper::success([
                'invoice_url' => $PayOrder->payment->invoice_url,
                'invoice_id' => $PayOrder->payment->external_id,
            ], 'Invoice sudah dibuat sebelumnya', 202);
        }

        $order = Order::with(['details', 'user'])->find($request->order_id);
      
            // CEK STOK
            foreach ($order->details as $detail) {
                $product = Product::findOrFail($detail->product_id);
    
                if ($product->qty < $detail->quantity) {
                    return ResponseHelper::error('Stok produk tidak mencukupi', 400);
                }
            }
            // Cek Ulang Harga Total
            $totalAmount = $order->details->sum(function ($detail) {
                return $detail->price * $detail->quantity;
            });
    
            $invoiceApi = new InvoiceApi();
            
    
            $params = [
                'external_id' => 'invoice-' . time(),
                'description' => 'Pembayaran untuk order#' . $order->id,
                'amount' => $totalAmount,
                'invoice_duration' => 3600,
                'currency' => 'IDR',
                'customer' => [
                    'given_names' => $order->user->name,
                    'email' => $order->user->email,
                ],
            ];
            try {
            $invoice = $invoiceApi->createInvoice($params);
    
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'Xendit',
                'payment_status' => 'PENDING',
                'payment_date' => now(),
                'external_id' => $invoice['external_id'],
                'invoice_url' => $invoice['invoice_url'],
                'invoice_id' => $invoice['id'],
            ]);
    
            $invoiceData = [
                'invoice_url' => $invoice['invoice_url'],
                'invoice_id' => $invoice['id'],
            ];
            return ResponseHelper::success($invoiceData, 'Invoice created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    public function callback(Request $request)
    {   
        $payload = $request->all();

        $invoiceCallback = new InvoiceCallback($payload);

        // $externalId = $request->external_id;
        // $status = $request->status;
        if($invoiceCallback->getStatus() === 'PAID'){
            $externalId = $invoiceCallback->getExternalId();
            $status = $invoiceCallback->getStatus();
            $payment = Payment::with('order.details')->where('external_id', $externalId)->first();
            if($payment){
                $payment->payment_status = $status;
                $payment->payment_date = now();
                $payment->save();
            }
            foreach ($payment->order->details as $detail) {
                $product = Product::findOrFail($detail->product_id);
    
                // Cek ulang untuk safety
                if ($product->qty < $detail->quantity) {
                    return ResponseHelper::error('Stok produk tidak mencukupi', 400);
                }
    
                $product->qty -= $detail->quantity;
                $product->save();
            }
    
            return ResponseHelper::success(null, 'Pembayaran berhasil', 200);
        }
        
        // if (!$payment || $status !== 'PAID') {
        //     return ResponseHelper::error('Pembayaran tidak ditemukan', 404);
        // }
    
    
    
        // try {
        //     foreach ($payment->order->details as $detail) {
        //         $product = Product::findOrFail($detail->product_id);
    
        //         // Cek ulang untuk safety
        //         if ($product->qty < $detail->quantity) {
        //             return ResponseHelper::error('Stok produk tidak mencukupi', 400);
        //         }
    
        //         $product->qty -= $detail->quantity;
        //         $product->save();
        //     }
    
        //     $payment->status = 'PAID';
        //     $payment->save();
    
        //     DB::commit();
    
        //     return ResponseHelper::success(null, 'Pembayaran berhasil', 200);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return ResponseHelper::error($e->getMessage(), 500);
        // }
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
