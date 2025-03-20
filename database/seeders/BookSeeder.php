<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;

class BookSeeder extends Seeder
{
    public function run()
    {
        $books = [
            ['title' => 'Harry Potter and the Philosopher\'s Stone', 'authors' => ['J.K. Rowling'], 'image' => 'harry_potter.jpg'],
            ['title' => '1984', 'authors' => ['George Orwell'], 'image' => '1984.jpg'],
            ['title' => 'Foundation', 'authors' => ['Isaac Asimov'], 'image' => 'foundation.jpg'],
            ['title' => 'Norwegian Wood', 'authors' => ['Haruki Murakami'], 'image' => 'norwegian_wood.jpg'],
            ['title' => 'Murder on the Orient Express', 'authors' => ['Agatha Christie'], 'image' => 'murder_orient_express.jpg'],
            ['title' => 'The Shining', 'authors' => ['Stephen King'], 'image' => 'the_shining.jpg'],
            ['title' => 'The Hobbit', 'authors' => ['J.R.R. Tolkien'], 'image' => 'the_hobbit.jpg'],
            ['title' => 'Sapiens: A Brief History of Humankind', 'authors' => ['Yuval Noah Harari'], 'image' => 'sapiens.jpg'],
            ['title' => 'The Selfish Gene', 'authors' => ['Richard Dawkins'], 'image' => 'selfish_gene.jpg'],
            ['title' => 'Cosmos', 'authors' => ['Carl Sagan'], 'image' => 'cosmos.jpg'],
        ];

        foreach ($books as $bookData) {
            // Simpan gambar ke storage jika belum ada
            $imagePath = 'images/' . $bookData['image'];
            if (!Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->put($imagePath, file_get_contents(public_path('default_images/' . $bookData['image'])));
            }

            // Buat path sesuai dengan format yang digunakan di controller
            $imageUrl = config('app.url') . '/storage/' . $imagePath;

            // Simpan buku
            $book = Book::firstOrCreate(
                ['title' => $bookData['title']],
                ['image' => $imageUrl]
            );

            // Pastikan author ada
            $authorIds = collect($bookData['authors'])->map(function ($authorName) {
                return Author::firstOrCreate(['name' => $authorName])->id;
            });

            // Attach authors dan categories
            $book->authors()->sync($authorIds);
            $categoryIds = Category::inRandomOrder()->limit(rand(1, 3))->pluck('id');
            $book->categories()->sync($categoryIds);
        }
    }
}
