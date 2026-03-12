<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posts extends Model {
    use HasFactory;

    protected $fillable = [
        "title", "content", "thumbnail",
    ];

    public function quizz() {
        return $this->hasOne(Quizzes::class, "post_id", "id");
    }
}
