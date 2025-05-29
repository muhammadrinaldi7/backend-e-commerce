<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{   
    use HasUuid;
    protected $fillable = ['order_id', 'payment_date', 'payment_method', 'payment_status'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
