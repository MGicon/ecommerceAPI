<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Models\Product;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexProductRequest $request)
    {
        $data = $request->validated();

        $query = Product::isLive(true)->with('main_image');

        $query->when(isset($data['query']) , function($query) use($data){
           $query->where('name' , 'like' , '%'.$data['query'].'%'); 
        })
        ->when(isset($data['is_offer']) , function($query) use($data){
            $query->where('special_offer' , '>' , Carbon::now());
        })
        ->when(isset($data['is_daily_offer']) , function($query) use($data){
            $query->where('daily_offer' , '>' , Carbon::now());
        })
        ->when(isset($data['sort_by']) , function($query) use($data){
            if($data['asc']){
                $query->orderBy($data['sort_by']);
            } else{
                $query->orderByDesc($data['sort_by']);
            }
        })->when(isset($data['category_id']) , function($query) use($data){
            $query->where('category_id' , $data['category_id']);
        });

        $products = $query->paginate($data['per_page'] ?? 15);


        return $this->respondOk(ProductResource::collection($products)->response()->getData(), 'Products fetched successfully');
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user->id;
        
        $product = Product::create($data);

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection("main");
        }

        if ($request->hasFile('additional_images')) {
            $product
                ->addMultipleMediaFromRequest(['additional_images'])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection("additional_images");
                });
        }

        $product->load(['main_image' , 'additional_images']);
        return $this->respondCreated(ProductResource::make($product), 'product created successfully');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (!$product->live) {
            return $this->respondNotFound('Product not found.');
        }

        $product->load(['main_image' , 'additional_images']);

        $product->setRelation('ratings',  $product->ratings()->paginate());
        
        $product->setRelation('similarProducts',  Product::where('category_id' , $product->category_id)
        ->where('id' , '!=' , $product->id)->take(5)
        ->inRandomOrder()->get());
        
        return $this->respondOk(ProductResource::make($product), 'product fetched successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $product->update($data);

        if ($request->hasFile('image')) {
            $product->clearMediaCollection("main");
            $product->addMediaFromRequest('image')->toMediaCollection("main");
        }

        if ($request->hasFile('additional_images')) {
            $product->clearMediaCollection("additional_images");

            $product
            ->addMultipleMediaFromRequest(['additional_images'])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection("additional_images");
                });
        }

        $product->load(['main_image' , 'additional_images']);

        return $this->respondOk(ProductResource::make($product), 'product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if(File::exists($product->image)) {
            File::delete($product->image);
        }   
        
        $product->delete();
        return $this->respondNoContent();
    }
}
