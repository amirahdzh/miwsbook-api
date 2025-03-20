<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Fiction',
            'Non-fiction',
            'Science',
            'History',
            'Biography',
            'Technology',
            'Fantasy',
            'Horror',
            'Mystery',
            'Romance'
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
