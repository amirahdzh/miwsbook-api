<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['title', 'summary', 'image', 'category_id', 'stock'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    // public function borrows()
    // {
    //     return $this->belongsToMany(User::class, 'borrows')
    //         ->withPivot('return_date', 'borrow_date');
    // }
}
