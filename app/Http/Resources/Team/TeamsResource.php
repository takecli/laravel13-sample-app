<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [
            'teams' => [],
        ];
        foreach ($this->resource->teams as $team) {
            $resource['teams'][] = [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'public_status' => $team->publicStatus,
            ];
        }
        $resource['total'] = $this->resource->total;

        return $resource;
    }
}
