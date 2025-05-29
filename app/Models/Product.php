<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuid;
    protected $fillable = ['product_name', 'image_product', 'gallery_product', 'price', 'qty', 'description', 'category_id'];

    protected $casts = [
        'gallery_product' => 'array',
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function detailOrders() {
        return $this->hasMany(Detail_order::class);
    }
}
