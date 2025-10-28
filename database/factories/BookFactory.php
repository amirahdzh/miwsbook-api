<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition()
    {
        // Menggunakan locale dari konfigurasi app.php
        $faker = \Faker\Factory::create(config('app.faker_locale'));

        $categoryId = Category::exists() ? Category::inRandomOrder()->first()->id : Category::factory();

        return [
            'title' => $faker->sentence(),
            'summary' => $faker->paragraph(),
            'stock' => $faker->numberBetween(1, 50),
            'image' => Storage::url('images/default-book-image.jpg'),
            'category_id' => $categoryId,
        ];
    }
}
