<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $image = $this->getFirstMediaUrl("main");
        if ($image == "") {
            $image = null;
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'price' => $this->price,
            'priceBefore' => $this->priceBefore,
            'quantity' => $this->quantity,
            'image' => $image,
            'additional_images' => MediaResource::collection($this->whenLoaded("additional_images")),
            'description' => $this->description,
            'live' => $this->live,
            'special_offer' => $this->special_offer,
            'daily_offer' => $this->daily_offer,
            'similarProducts' => ProductResource::collection($this->whenLoaded('similarProducts')),
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
        ];
    }
}
