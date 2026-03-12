<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizzAnswers extends Model
{
    use HasFactory;

    protected $fillable = [
        "quizz_id",
        "user_id",
        "option",
    ];

    public function quizz()
    {
        return $this->belongsTo(Quizzes::class, "quizz_id", "id");
    }
}
