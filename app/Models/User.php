<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\OAuthType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable , HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'social_id',
        'social_type', // 0 = google , 1 = facebook
        'phone',
        'ban'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected function ban(): Attribute{
        return Attribute::make(
            // get: fn ($value) => isset($value) ? $value->format('Y-m-d H:i:s A') : null,
            set: fn (string $value) =>  isset($value) ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    protected function socialType(): Attribute
        {
            return Attribute::make(
                get: fn ($value) => isset($value) ? OAuthType::fromValue($value)->key : null,
                set: fn ($value) => isset($value) ? OAuthType::fromKey($value) : null,
            );
    }

    public function products()
    {
        return $this->hasMany(Product::class , 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class , 'user_id');
    }

    public function cart()
    {
        return $this->belongsToMany(Product::class , 'product_user')->withPivot('quantity');
    }

    public function wish_list()
    {
        return $this->belongsToMany(Product::class , 'wish_list');
    }
}

