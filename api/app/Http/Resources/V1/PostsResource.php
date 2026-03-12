<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\QuizzResource;

class PostsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "content" => $this->content,
            "thumbnail" => $this->thumbnail,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "quizz" => new QuizzResource($this->whenLoaded("quizz")),
        ];
    }
}
