<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Models\Order;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexOrderRequest $request)
    {

        $data = $request->validated();
        $per_page = $data['per_page'] ?? 15;

        $query = Order::query()->latest();

        // $query->when(isset($data['query']) , function($query) use($data){
        //     $query->where('name' , 'like' , '%' . $data['query'] . '%');
        // });
        
        $query->when(isset($data['status']) , function($query) use($data){
            $query->where('status' , $data['status']);
        });
        $query->when(isset($data['sort_by']) , function($query) use($data){
            if($data['asc']){
                $query->orderBy($data['sort_by']);
            } else{
                $query->orderByDesc($data['sort_by']);
            }
        })->when(isset($data['from']) , function($query) use($data){
            $query->whereBetween('created_at' , '>=' , $data['from']);
        })->when(isset($data['to']) , function($query) use($data){
            $query->whereBetween('created_at' , '<=' , $data['to']);
        });

        $orders = $query->paginate($per_page);

        return $this->respondOk($orders, 'Orders fetched successfully');

    }


    /**
     * Display a listing of the resource.
     */
    public function my_order(IndexOrderRequest $request)
    {

        $data = $request->validated();
        $user = $request->user;
        $per_page = $data['per_page'] ?? 15;

        $query = $user->orders()->latest();

        $query->when(isset($data['query']) , function($query) use($data){
            $query->where('name' , 'like' , '%' . $data['query'] . '%');
        });

        $query->when(isset($data['status']) , function($query) use($data){
            $query->where('status' , $data['status']);
        });

        $query->when(isset($data['sort_by']) , function($query) use($data){
            if($data['asc']){
                $query->orderBy($data['sort_by']);
            } else{
                $query->orderByDesc($data['sort_by']);
            }
        });

        $orders = $query->paginate($per_page);

        return $this->respondOk($orders, 'Orders fetched successfully');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $user = $request->user;

        DB::beginTransaction();

        $result = DB::table('product_user')
        ->join('products', 'product_user.product_id', '=', 'products.id')
        ->where('product_user.user_id', $user->id)
        ->select(
            'products.id',
            'products.price',
            'products.quantity as productQuantity',
            'product_user.quantity as cartQuantity',
            DB::raw('(product_user.quantity * products.price) as total_price')
        )
        ->get();

        if(count($result) == 0) {
            return $this->respondError("Cart is empty");
        }

        $order = Order::create([
            'total' => 0,
            'user_id' => $user->id,
        ]);

        $totalSum = 0;

        foreach($result as $row) {

            $totalSum += $row->total_price;

            if($row->cartQuantity > $row->productQuantity) {
                DB::rollBack();
                return $this->respondError("Insufficient quantity, existing quantity is ".$row->productQuantity);
            }

            // query builder to update the product quantity
            DB::table('products')->where('id', $row->id)->update(['quantity' => $row->productQuantity - $row->cartQuantity]);

            $order->products()->attach($row->id , [
                'quantity' => $row->cartQuantity,
                'price' => $row->total_price
            ]);

        }

        $totalSum = round($totalSum , 2);

        // Clear the cart after successful checkout
        $user->cart()->detach();
        
        $order->update(['total' => $totalSum]);
        

        DB::commit();

        return $this->respondOk($totalSum , "Checkout successful");

    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order , Request $request)
    {
        if($order->user_id != $request->user->id && !$request->user->hasRole('delivery') && !$request->user->hasRole('superAdmin')){
            return response(["message" => "Unauthorized"], 403);
        }
        return $this->respondOk($order->load('products') , 'Order fetched successfully');
    }

   /**
     * Update the specified resource in storage.
     */
    public function update(Order $order , UpdateOrderRequest $request)
    {
        $data = $request->validated();

        if($order->status == "Completed" || $order->status == "Canceled"){
            return $this->respondError("Cannot change status of an order that is Completed or Canceled " . "current status : " . $order->status);
        }

        if(($data['status'] == "Confirmed" || $data['status'] == "Rejected") && $order->status != "Pending"){ 
            return $this->respondError("Cannot Confirme or Reject order that is not pending ". "current status : " . $order->status);
        }

        if($data['status'] == "Completed" && $order->status != "Confirmed"){ 
            return $this->respondError("Cannot Complete an order that is not confirmed ". "current status : " . $order->status);
        }

        $order->update([
            'status' => $data['status'],
        ]);

        return $this->respondNoContent();
        
    }
}
