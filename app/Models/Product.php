<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Http\Traits\CustomRateable;

class Product extends Model implements HasMedia
{
    use HasFactory , InteractsWithMedia , CustomRateable;

    public $fillable = ['name', 'description', 'price', 'priceBefore','user_id', 'category_id' , 'live' , 'quantity' , 'special_offer' , 'daily_offer'];
    
    public $casts = [
        "category_id" => "integer",
        "price" => "float",
        "priceBefore" => "float",
    ];

    protected function specialOffer(): Attribute{
        return Attribute::make(
            // get: fn ($value) => isset($value) ? $value->format('Y-m-d H:i:s A') : null,
            set: fn (string $value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    protected function dailyOffer(): Attribute{
        return Attribute::make(
            // get: fn ($value) => isset($value) ? $value->format('Y-m-d H:i:s A') : null,
            set: fn (string $value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // The users had this product in their cart
    public function users()
    {
        return $this->belongsToMany(User::class , 'product_user')->withPivot('quantity');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class , 'order_product')->withPivot('quantity');
    }

    public function scopeIsLive($query , bool $live)
    {
        return $query->where('live', $live);
    }

    public function additional_images()
    {
        return $this->media()->where('collection_name', 'additional_images');
    }

    public function main_image()
    {
        return $this->media()->where('collection_name', 'main');
    }

}
