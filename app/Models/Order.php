<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuid;
    protected $fillable = ['user_id', 'order_date', 'total_price', 'status', 'shipping_address'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function details() {
        return $this->hasMany(Detail_order::class);
    }

    public function payment() {
        return $this->hasOne(Payment::class);
    }
}
