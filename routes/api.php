<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\Auth\SocialiteController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\RatingController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\WishListController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Routes are in auth.php

Route::get('test', function(){
    return response()->json(User::with('roles')->get());
});

Route::apiResource("product", ProductController::class , ['only' => ['index', 'show']]);
Route::apiResource("category", CategoryController::class , ['only' => ['index', 'show']]);

Route::middleware('loggedIn')->group(function() {
    
    Route::post("user/update_profile", [UserController::class , 'update_profile']);
    Route::get("user/my_profile", [UserController::class , 'my_profile']);

    Route::middleware('client')->group( function() {

        Route::get("/my-cart", [CartController::class, "show"]);
        Route::get("/add-to-cart", [CartController::class, "store"]);
        Route::delete("/remove-from-cart", [CartController::class, "destroy"]);
        Route::get("/my-orders", [OrderController::class, "my_order"]);
        Route::apiResource("order", OrderController::class , ['except' => ['update', 'index']]);

        Route::apiResource("rating", RatingController::class , ['only' => ['index' , 'store' , 'destroy']]);
        Route::apiResource("wishlist", WishListController::class , ['only' => ['index','store' ,'destroy']]);
    });

    Route::middleware('superAdmin')->group(function() {
        Route::apiResource("product", ProductController::class)->except(['index', 'show']);
        Route::apiResource("category", CategoryController::class)->except(['index', 'show']);
        Route::apiResource("order", OrderController::class , ['only' => ['update']]);

        Route::apiResource("user", UserController::class);
        Route::get("user/ban/{user}", [UserController::class , "ban"]);
    });

    Route::middleware('has_any_role:client,superAdmin,delivery')->group(function() {
        Route::apiResource("order", OrderController::class , ['only' => ['show']]);
    });

    Route::middleware('has_any_role:superAdmin,delivery')->group(function() {
        Route::apiResource("order", OrderController::class , ['only' => ['index']]);
    });

});


// Route::prefix("order")->middleware("loggedIn")->group(function(){
//     Route::get("/complete/{order}", [OrderController::class, "completeOrder"]);
//     Route::get("/cancel/{order}", [OrderController::class, "cancelOrder"]);
// });


Route::get("category/show-admin/{category}", [CategoryController::class , "show_admin"])->middleware("loggedIn");