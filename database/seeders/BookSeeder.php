<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;

class BookSeeder extends Seeder
{
    public function run()
    {
        $books = [
            ['title' => 'Harry Potter and the Philosopher\'s Stone', 'authors' => ['J.K. Rowling']],
            ['title' => '1984', 'authors' => ['George Orwell']],
            ['title' => 'Foundation', 'authors' => ['Isaac Asimov']],
            ['title' => 'Norwegian Wood', 'authors' => ['Haruki Murakami']],
            ['title' => 'Murder on the Orient Express', 'authors' => ['Agatha Christe']],
            ['title' => 'The Shining', 'authors' => ['Stephen King']],
            ['title' => 'The Hobbit', 'authors' => ['J.R.R. Tolkien']],
            ['title' => 'Sapiens: A Brief History of Humankind', 'authors' => ['Yuval Noah Harari']],
            ['title' => 'The Selfish Gene', 'authors' => ['Richard Dawkins']],
            ['title' => 'Cosmos', 'authors' => ['Isaac Asimov']],
        ];

        foreach ($books as $bookData) {
            $book = Book::create(['title' => $bookData['title']]);

            // Attach multiple authors (many-to-many)
            $authorIds = Author::whereIn('name', $bookData['authors'])->pluck('id');
            $book->authors()->attach($authorIds);

            // Attach buku ke 1-3 kategori secara random
            $categoryIds = Category::inRandomOrder()->limit(rand(1, 3))->pluck('id');
            $book->categories()->attach($categoryIds);

            // if ($authorIds->isNotEmpty()) {
            //     $book->authors()->attach($authorIds);
            // }
        }
    }
}
