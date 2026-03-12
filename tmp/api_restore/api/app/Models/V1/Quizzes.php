<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quizzes extends Model
{
    use HasFactory;

    protected $fillable = [
        "question",
        "answers",
        "post_id",
        "first_answer",
        "second_answer",
        "third_answer",
        "fourth_answer",
    ];

    public function post()
    {
        return $this->belongsTo(Posts::class, "post_id", "id");
    }
}
