<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'bio', 'photo'];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_author');
    }
}
