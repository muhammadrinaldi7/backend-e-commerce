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
    
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
    
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 422);
        }
    
        $order = Order::with(['payment', 'details', 'user'])->findOrFail($request->order_id);
    
        // Case 1: Sudah dibayar
        if ($order->payment && $order->payment->payment_status === 'PAID') {
            return ResponseHelper::error('Order sudah dibayar', 400);
        }
    
        // Case 2: Invoice masih pending → cek apakah expired
        if ($order->payment && $order->payment->payment_status === 'PENDING') {
            $invoiceApi = new InvoiceApi();
            try {
                $xenditInvoice = $invoiceApi->getInvoices($order->payment->invoice_id);
    
                if ($xenditInvoice['status'] === 'EXPIRED') {
                    // Update status db
                    $order->payment->payment_status = 'EXPIRED';
                    $order->payment->save();
                } else {
                    // Invoice masih aktif
                    return ResponseHelper::success([
                        'invoice_url' => $order->payment->invoice_url,
                        'invoice_id'  => $order->payment->invoice_id,
                    ], 'Invoice masih aktif', 202);
                }
            } catch (\Exception $e) {
                return ResponseHelper::error('Gagal cek invoice: ' . $e->getMessage(), 500);
            }
        }
    
        // Validasi stok
        foreach ($order->details as $detail) {
            $product = Product::findOrFail($detail->product_id);
            if ($product->qty < $detail->quantity) {
                return ResponseHelper::error("Stok produk '{$product->name}' tidak cukup", 400);
            }
        }
    
        // Hitung ulang total
        $totalAmount = $order->details->sum(fn($detail) => $detail->price * $detail->quantity);
    
        $invoiceParams = [
            'external_id'     => 'invoice-' . time(),
            'description'     => 'Pembayaran untuk order #' . $order->id,
            'amount'          => $totalAmount,
            'invoice_duration'=> 3600, // 1 jam
            'currency'        => 'IDR',
            'customer'        => [
                'given_names' => $order->user->name,
                'email'       => $order->user->email,
            ],
        ];
    
        try {
            $invoice = (new InvoiceApi())->createInvoice($invoiceParams);
    
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method' => 'Xendit',
                    'payment_status' => 'PENDING',
                    'payment_date'   => now(),
                    'external_id'    => $invoice['external_id'],
                    'invoice_url'    => $invoice['invoice_url'],
                    'invoice_id'     => $invoice['id'],
                ]
            );
    
            return ResponseHelper::success([
                'invoice_url' => $invoice['invoice_url'],
                'invoice_id'  => $invoice['id'],
            ], 'Invoice berhasil dibuat', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error('Gagal membuat invoice: ' . $e->getMessage(), 500);
        }
    }
    
    
    
    public function callback(Request $request)
    {   
        $payload = $request->all();

        $invoiceCallback = new InvoiceCallback($payload);

        if ($invoiceCallback->getStatus() === 'EXPIRED') {
            $payment = Payment::where('external_id', $invoiceCallback->getExternalId())->first();
            if ($payment) {
                $payment->payment_status = 'EXPIRED';
                $payment->save();
            }
            return ResponseHelper::success($payment, 'Pembayaran expired', 200);
        }
        
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
