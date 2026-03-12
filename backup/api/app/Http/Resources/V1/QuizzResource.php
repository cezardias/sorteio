<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizzResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "question" => $this->question,
            "answers" => $this->answers,
            "id" => $this->id,
            "firstAnswer" => $this->first_answer,
            "secondAnswer" => $this->second_answer,
            "thirdAnswer" => $this->third_answer,
            "fourthAnswer" => $this->fourth_answer,
        ];
    }
}
