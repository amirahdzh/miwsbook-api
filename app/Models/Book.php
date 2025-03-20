<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['title', 'summary', 'image', 'stock'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }


    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    public function getAvailabilityAttribute()
    {
        return $this->stock > 0;
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    // public function borrows()
    // {
    //     return $this->belongsToMany(User::class, 'borrows')
    //         ->withPivot('return_date', 'borrow_date');
    // }
}
