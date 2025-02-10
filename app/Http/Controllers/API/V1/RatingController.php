<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rating\StoreRatingRequest;
use App\Http\Requests\Rating\UpdateRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use willvincent\Rateable\Rating;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ratings = Rating::where('user_id' , $request->user->id)->with('rateable')->paginate();
        return $this->respondOk(RatingResource::collection($ratings)->response()->getData(), 'Ratings fetched successfully');
    }

    /**
     * Store a newly created resource in storage. or Update Existing one
     */
    public function store(StoreRatingRequest $request)
    {
        $data = $request->validated();

        $prodcut = Product::find($data['product_id']);

        $prodcut->rateOnce($data['rate'] , $data['comment'] ?? null , $request->user->id);

        return $this->respondNoContent();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
    public function update(UpdateRatingRequest $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating , Request $request)
    {
        if($rating->user_id != $request->user->id){
            return $this->respondNotFound("Rating not found");
        }

        $rating->delete();
        return $this->respondNoContent();
    }
}
