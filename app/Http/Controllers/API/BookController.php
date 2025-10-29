<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\BookRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'isOwner'])->except(['index', 'show', 'search']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Load kategori dan author
            $books = Book::with(['categories', 'authors'])->paginate($perPage, ['*'], 'page', $page);

            $data = collect($books->items())->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'summary' => $book->summary,
                    'stock' => $book->stock,
                    'image' => $book->image,
                    'availability' => $book->availability,
                    'categories' => $book->categories->map(fn($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]),
                    'authors' => $book->authors->map(fn($author) => [
                        'id' => $author->id,
                        'name' => $author->name,
                        'photo' => $author->photo,
                        'bio' => $author->bio,
                    ]),
                    'created_at' => $book->created_at,
                    'updated_at' => $book->updated_at,
                ];
            });

            return response()->json([
                'message' => 'Data retrieved successfully',
                'data' => $data,
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        Log::info('Validated data:', $request->validated());

        $data = $request->validated();
        $providedIds = $request->input('category_ids', []) ?: [];
        $providedNames = $request->input('categories', []) ?: [];
        $authorIds = $request->input('author_ids', []) ?: [];

        // Normalize category names
        $normalizedNames = collect($providedNames)
            ->map(fn($n) => trim(preg_replace('/\s+/', ' ', (string)$n)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        DB::beginTransaction();
        try {
            // create/find categories by name
            $createdIds = [];
            foreach ($normalizedNames as $name) {
                // store with title case to keep readable names; change if you prefer
                $storedName = Str::title($name);
                $category = Category::firstOrCreate(['name' => $storedName]);
                $createdIds[] = $category->id;
            }

            // merge unique ids
            $categoryIds = collect($providedIds)
                ->merge($createdIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($request->hasFile('image')) {
                $imageName = time() . '-image.' . $request->image->extension();
                $request->image->storeAs('images', $imageName);
                $path = config('app.url') . '/storage/images/';
                $data['image'] = $path . $imageName;
            }

            $book = Book::create($data);

            // Attach categories and authors to the book
            if (!empty($categoryIds)) {
                $book->categories()->attach($categoryIds);
            }

            if (!empty($authorIds)) {
                $book->authors()->attach($authorIds);
            }

            DB::commit();

            return response()->json([
                'message' => 'Data added successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create book: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create book', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::with(['categories', 'authors', 'borrows'])->find($id);

        if (!$book) {
            return response()->json([
                'message' => 'ID not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Data detail retrieved successfully',
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'summary' => $book->summary,
                'image' => $book->image,
                'year' => $book->year,
                'stock' => $book->stock,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
                'categories' => $book->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ]),
                'authors' => $book->authors->map(fn($author) => [
                    'id' => $author->id,
                    'name' => $author->name,
                ]),
                'list_borrows' => $book->borrows->map(fn($borrow) => [
                    'id' => $borrow->id,
                    'load_date' => $borrow->load_date,
                    'borrow_date' => $borrow->borrow_date,
                    'user_id' => $borrow->user_id,
                    'book_id' => $borrow->book_id,
                    'created_at' => $borrow->created_at,
                    'updated_at' => $borrow->updated_at,
                ]),
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, string $id)
    {
        Log::info('Request Data:', $request->all());

        $data = $request->validated();
        $providedIds = $request->input('category_ids', []) ?: [];
        $providedNames = $request->input('categories', []) ?: [];
        $authorIds = $request->input('author_ids', []) ?: [];

        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Book ID not found'
            ], 404);
        }

        if ($request->hasFile('image')) {
            if ($book->image) {
                $imageName = basename($book->image);
                Storage::delete('images/' . $imageName);
            }

            $imageName = time() . '-image.' . $request->image->extension();
            $request->image->storeAs('images', $imageName);

            $path = config('app.url') . '/storage/images/';
            $data['image'] = $path . $imageName;
        }

        DB::beginTransaction();
        try {
            $book->update($data);

            // Normalize names and create missing categories
            $normalizedNames = collect($providedNames)
                ->map(fn($n) => trim(preg_replace('/\s+/', ' ', (string)$n)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $createdIds = [];
            foreach ($normalizedNames as $name) {
                $storedName = Str::title($name);
                $category = Category::firstOrCreate(['name' => $storedName]);
                $createdIds[] = $category->id;
            }

            $categoryIds = collect($providedIds)
                ->merge($createdIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Update categories & authors
            $book->categories()->sync($categoryIds);
            $book->authors()->sync($authorIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update book: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update book', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Book updated successfully',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'ID not found'
            ], 404);
        }

        if ($book->image) {
            $imageName = basename($book->image);
            Storage::delete('public/images/' . $imageName);
        }

        // Hapus hubungan kategori & author sebelum menghapus buku
        $book->categories()->detach();
        $book->authors()->detach();

        $book->delete();

        return response()->json([
            'message' => "Book with ID $id deleted successfully",
        ], 200);
    }


    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     if (!$query) {
    //         return response()->json(['error' => 'Query parameter is required'], 400);
    //     }

    //     // Search for books by title only
    //     $books = Book::where('title', 'like', "%{$query}%")
    //         ->get();

    //     return response()->json($books);
    // }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([
                'message' => 'Query parameter is required',
                'data' => []
            ], 400);
        }

        // Search for books by title only
        $books = Book::where('title', 'like', "%{$query}%")
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'summary' => $book->summary,
                    'stock' => $book->stock,
                    'image' => $book->image,
                    'availability' => $book->availability,
                    'categories' => $book->categories->map(fn($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]),
                    'authors' => $book->authors->map(fn($author) => [
                        'id' => $author->id,
                        'name' => $author->name,
                    ]),
                    'created_at' => $book->created_at,
                    'updated_at' => $book->updated_at,
                ];
            });

        return response()->json([
            'message' => 'Search results retrieved successfully',
            'data' => $books
        ]);
    }

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');
    //     $source = $request->input('source', 'all'); // Bisa 'local', 'external', atau 'all'

    //     if (!$query) {
    //         return response()->json(['error' => 'Query parameter is required'], 400);
    //     }

    //     // 1️⃣ Cari di database lokal dulu
    //     $books = Book::where('title', 'like', "%{$query}%")->get();

    //     // Jika hanya mencari di koleksi pribadi
    //     if ($source === 'local') {
    //         return response()->json($books);
    //     }

    //     // 2️⃣ Jika source 'all' atau 'external', cari di Open Library API juga
    //     $externalBooks = [];
    //     if ($source !== 'local') {
    //         $response = Http::get("https://openlibrary.org/search.json?q=" . urlencode($query));
    //         if ($response->successful()) {
    //             $data = $response->json();
    //             $externalBooks = collect($data['docs'])->map(function ($book) {
    //                 return [
    //                     'title' => $book['title'] ?? 'Unknown Title',
    //                     'author' => $book['author_name'][0] ?? 'Unknown Author',
    //                     'year' => $book['first_publish_year'] ?? 'Unknown Year',
    //                     'cover' => isset($book['cover_i']) ? "https://covers.openlibrary.org/b/id/{$book['cover_i']}-M.jpg" : null,
    //                     'source' => 'external', // Tandai bahwa ini dari Open Library
    //                 ];
    //             });
    //         }
    //     }

    //     return response()->json([
    //         'local_books' => $books,
    //         'external_books' => $externalBooks
    //     ]);
    // }

    public function addFromOpenLibrary(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'author' => 'nullable|string',
            'year' => 'nullable|integer',
            'cover' => 'nullable|url',
        ]);

        $book = Book::create([
            'title' => $request->title,
            'summary' => 'Imported from Open Library',
            'year' => $request->year,
            'image' => $request->cover,
            'stock' => 1, // Bisa disesuaikan
        ]);

        return response()->json([
            'message' => 'Book added successfully',
            'book' => $book,
        ], 201);
    }
}
