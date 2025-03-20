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
            [
                'title' => 'Harry Potter and the Philosopher\'s Stone',
                'authors' => ['J.K. Rowling'],
                'image' => 'harry_potter.jpg',
                'summary' => 'A young wizard, Harry Potter, discovers his magical heritage on his 11th birthday and attends Hogwarts School of Witchcraft and Wizardry.'
            ],
            [
                'title' => '1984',
                'authors' => ['George Orwell'],
                'image' => '1984.jpg',
                'summary' => 'A dystopian novel that delves into the dangers of totalitarianism and extreme political ideology.'
            ],
            [
                'title' => 'Foundation',
                'authors' => ['Isaac Asimov'],
                'image' => 'foundation.jpg',
                'summary' => 'A scientist predicts the fall of the Galactic Empire and devises a plan to save civilization through a Foundation.'
            ],
            [
                'title' => 'Norwegian Wood',
                'authors' => ['Haruki Murakami'],
                'image' => 'norwegian_wood.jpg',
                'summary' => 'A nostalgic story of love and loss, exploring the struggles of youth in 1960s Japan.'
            ],
            [
                'title' => 'Murder on the Orient Express',
                'authors' => ['Agatha Christie'],
                'image' => 'murder_orient_express.jpg',
                'summary' => 'Detective Hercule Poirot investigates a murder on the luxurious Orient Express train.'
            ],
            [
                'title' => 'The Shining',
                'authors' => ['Stephen King'],
                'image' => 'the_shining.jpg',
                'summary' => 'A family stays in an isolated hotel where the father descends into madness, haunted by supernatural forces.'
            ],
            [
                'title' => 'The Hobbit',
                'authors' => ['J.R.R. Tolkien'],
                'image' => 'the_hobbit.jpg',
                'summary' => 'A reluctant hobbit embarks on an adventure to reclaim a lost kingdom from a dragon.'
            ],
            [
                'title' => 'Sapiens: A Brief History of Humankind',
                'authors' => ['Yuval Noah Harari'],
                'image' => 'sapiens.jpg',
                'summary' => 'A historical overview of how Homo sapiens evolved and shaped the modern world.'
            ],
            [
                'title' => 'The Selfish Gene',
                'authors' => ['Richard Dawkins'],
                'image' => 'selfish_gene.jpg',
                'summary' => 'An exploration of evolution, emphasizing how genes drive biological success.'
            ],
            [
                'title' => 'Cosmos',
                'authors' => ['Carl Sagan'],
                'image' => 'cosmos.jpg',
                'summary' => 'A journey through space and time, explaining the wonders of the universe in an accessible way.'
            ],
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
                ['image' => $imageUrl, 'summary' => $bookData['summary']]
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
