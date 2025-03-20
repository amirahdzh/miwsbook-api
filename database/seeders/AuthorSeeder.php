<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Author;

class AuthorSeeder extends Seeder
{
    public function run()
    {
        $defaultPhoto = 'author-1.jpg';
        $photoPath = 'authors/' . $defaultPhoto;

        // Pastikan gambar default ada di storage
        if (!Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->put($photoPath, file_get_contents(public_path('default_images/' . $defaultPhoto)));
        }

        $authors = [
            ['name' => 'J.K. Rowling', 'bio' => 'British author, best known for the Harry Potter series.'],
            ['name' => 'George Orwell', 'bio' => 'English novelist and journalist, famous for "1984" and "Animal Farm".'],
            ['name' => 'Isaac Asimov', 'bio' => 'American writer and professor, known for science fiction and popular science books.'],
            ['name' => 'Haruki Murakami', 'bio' => 'Japanese novelist known for blending magical realism with everyday life.'],
            ['name' => 'Agatha Christie', 'bio' => 'English writer, renowned for her detective novels featuring Hercule Poirot and Miss Marple.'],
            ['name' => 'Stephen King', 'bio' => 'American author famous for his horror, supernatural fiction, and suspense novels.'],
            ['name' => 'J.R.R. Tolkien', 'bio' => 'English writer, poet, and philologist, best known for "The Lord of the Rings".'],
            ['name' => 'Yuval Noah Harari', 'bio' => 'Israeli historian and author of "Sapiens: A Brief History of Humankind".'],
            ['name' => 'Richard Dawkins', 'bio' => 'British evolutionary biologist, famous for "The Selfish Gene".'],
            ['name' => 'Carl Sagan', 'bio' => 'American astronomer and science communicator, known for "Cosmos".'],
        ];

        foreach ($authors as $authorData) {
            Author::firstOrCreate(
                ['name' => $authorData['name']],
                [
                    'photo' => config('app.url') . '/storage/' . $photoPath,
                    'bio' => $authorData['bio'],
                ]
            );
        }
    }
}
