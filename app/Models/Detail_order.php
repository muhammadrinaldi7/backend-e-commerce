<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Detail_order extends Model
{
    use HasUuid;
    protected $fillable = ['order_id', 'product_id', 'quantity', 'price'];
    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
