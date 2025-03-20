<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Author;

class AuthorSeeder extends Seeder
{
    public function run()
    {
        $authors = [
            ['name' => 'J.K. Rowling'],
            ['name' => 'George Orwell'],
            ['name' => 'Isaac Asimov'],
            ['name' => 'Haruki Murakami'],
            ['name' => 'Agatha Christie'],
            ['name' => 'Stephen King'],
            ['name' => 'J.R.R. Tolkien'],
            ['name' => 'Yuval Noah Harari'],
            ['name' => 'Richard Dawkins'],
            ['name' => 'Carl Sagan'],
        ];

        foreach ($authors as $author) {
            Author::create($author);
        }
    }
}
