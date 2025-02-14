<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Requests\BookRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
            // Get pagination parameters
            $perPage = $request->input('per_page', 10); // Default to 10 per page
            $page = $request->input('page', 1);

            // Retrieve books with pagination
            $books = Book::with('category')->paginate($perPage, ['*'], 'page', $page);

            // Format the data as needed
            $data = $books->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'summary' => $book->summary,
                    'stock' => $book->stock,
                    'image' => $book->image,
                    'category' => [
                        'id' => $book->category->id,
                        'name' => $book->category->name,
                        'created_at' => $book->category->created_at,
                        'updated_at' => $book->category->updated_at,
                    ],
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

    // public function index(Request $request)
    // {
    //     try {
    //         $perPage = $request->input('per_page', 10);
    //         $page = $request->input('page', 1);

    //         $books = Book::with('category')->paginate($perPage, ['*'], 'page', $page);

    //         return response()->json([
    //             'message' => 'Data retrieved successfully',
    //             'data' => $books->items(),
    //             'current_page' => $books->currentPage(),
    //             'last_page' => $books->lastPage(),
    //             'per_page' => $books->perPage(),
    //             'total' => $books->total(),
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        // Log the validated data
        Log::info('Validated data:', $request->validated());

        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Create a unique name for the uploaded image
            $imageName = time() . '-image.' . $request->image->extension();

            // Save the image to the storage
            $request->image->storeAs('images', $imageName);

            // Replace the image field in the data with the new unique name
            $path = config('app.url') . '/storage/images/';
            $data['image'] = $path . $imageName;
        }

        Book::create($data);

        return response()->json([
            'message' => 'Data added successfully',
        ], 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::with(['category', 'borrows'])->find($id);

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
                'category_id' => $book->category_id,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
                'category' => $book->category ? [
                    'id' => $book->category->id,
                    'name' => $book->category->name,
                    'created_at' => $book->category->created_at,
                    'updated_at' => $book->category->updated_at,
                ] : null,
                'list_borrows' => $book->borrows->map(function ($borrow) {
                    return [
                        'id' => $borrow->id,
                        'load_date' => $borrow->load_date,
                        'borrow_date' => $borrow->borrow_date,
                        'user_id' => $borrow->user_id,
                        'book_id' => $borrow->book_id,
                        'created_at' => $borrow->created_at,
                        'updated_at' => $borrow->updated_at,
                        // Hapus pivot jika relasi bukan many-to-many
                    ];
                }),
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
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Book ID not found'
            ], 404);
        }

        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($book->image) {
                $imageName = basename($book->image);
                Storage::delete('public/images/' . $imageName);
            }

            // Create a unique name for the new image
            $imageName = time() . '-image.' . $request->image->extension();
            $request->image->storeAs('public/images', $imageName);

            $path = config('app.url') . '/storage/images/';
            $data['image'] = $path . $imageName;
        }

        $book->update($data);

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

        $book->delete();

        return response()->json([
            'message' => "Book with ID $id deleted successfully",
        ], 200);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['error' => 'Query parameter is required'], 400);
        }

        // Search for books by title only
        $books = Book::where('title', 'like', "%{$query}%")
            ->get();

        return response()->json($books);
    }
}
