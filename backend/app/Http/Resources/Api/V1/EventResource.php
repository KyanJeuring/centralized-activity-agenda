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
        $appName = $this->app_name ?? $this->whenLoaded('club', fn () => $this->club->name);
        $appSource = $this->app_source ?? $this->whenLoaded('club', fn () => $this->club->source);
        $appType = $this->app_type ?? $this->whenLoaded('club', fn () => $this->club->type);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'organizer' => $this->organizer,
            'start_date' => $this->start_date,
            'description' => $this->description,
            'location' => $this->location,
            'url' => $this->url,
            'app_id' => $this->app_id,
            'app_name' => $appName,
            'app_source' => $appSource,
            'app_type' => $appType,
            'img' => $this->img,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tags' => $this->tags ?? null,
        ];
    }
}
