<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'organizer' => $this->organizer,
            'start_date' => $this->start_date ? clone $this->start_date : null,
            'description' => $this->description,
            'location' => $this->location,
            'url' => $this->url,
            'app_id' => $this->app_id,
            'app_name' => $this->whenLoaded('club', fn () => $this->club->name),
            'app_source' => $this->whenLoaded('club', fn () => $this->club->source),
            'app_type' => $this->whenLoaded('club', fn () => $this->club->type),
            'img' => $this->img,
            'created_at' => $this->created_at ? clone $this->created_at : null,
            'updated_at' => $this->updated_at ? clone $this->updated_at : null,
        ];
    }
}
