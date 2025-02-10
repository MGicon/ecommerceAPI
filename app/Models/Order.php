<?php

namespace App\Models;

use App\Enums\OrderStatusType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $fillable = ['status', 'user_id', 'product_id', 'total'];

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class , 'order_product')->withPivot(['quantity' , 'price']);
    }


    protected function status(): Attribute
        {
            return Attribute::make(
                get: function ($value) {
                    if (is_numeric($value)){
                        return OrderStatusType::fromValue(intval($value))->key;
                    }else{
                        return OrderStatusType::fromValue($value)->key;
                    }
                },
                set: fn ($value) => OrderStatusType::fromKey($value),
            );
        }
    
}
